<?php
//
// +---------------------------------------------------------------------+
// | CODE INC. SOURCE CODE                                               |
// +---------------------------------------------------------------------+
// | Copyright (c) 2017 - Code Inc. SAS - All Rights Reserved.           |
// | Visit https://www.codeinc.fr for more information about licensing.  |
// +---------------------------------------------------------------------+
// | NOTICE:  All information contained herein is, and remains the       |
// | property of Code Inc. SAS. The intellectual and technical concepts  |
// | contained herein are proprietary to Code Inc. SAS are protected by  |
// | trade secret or copyright law. Dissemination of this information or |
// | reproduction of this material  is strictly forbidden unless prior   |
// | written permission is obtained from Code Inc. SAS.                  |
// +---------------------------------------------------------------------+
//
// Author:   Joan Fabrégat <joan@codeinc.fr>
// Date:     12/03/2018
// Time:     10:33
// Project:  lib-instantiator
//
declare(strict_types = 1);
namespace CodeInc\Instantiator;
use CodeInc\Instantiator\Exceptions\InterfaceWithoutAliasException;
use CodeInc\Instantiator\Exceptions\NewInstanceException;
use CodeInc\Instantiator\Exceptions\NotAnObjectException;
use CodeInc\Instantiator\Exceptions\ParamValueException;
use CodeInc\Instantiator\Exceptions\InstantiatorException;
use CodeInc\Instantiator\Exceptions\ParamTypeException;
use CodeInc\Instantiator\Exceptions\ClassNotFoundException;


/**
 * Class Instantiator
 *
 * @package Instantiator
 * @author Joan Fabrégat <joan@codeinc.fr>
 * @todo add object type hint when min compatibility >= 7.2
 */
class Instantiator
{
	/**
	 * Instantiated objects stack.
	 *
	 * @see Instantiator::addInstance()
	 * @see Instantiator::getInstance()
	 * @var object[]
	 */
	private $instances = [];

	/**
	 * Services aliases (other classes or interfaces)
	 *
	 * @see Instantiator::addAlias()
	 * @var string[]
	 */
	private $aliases = [];

	/**
	 * ServiceManager constructor.
	 *
	 * @throws InstantiatorException
	 * @throws \ReflectionException
	 */
	public function __construct()
	{
		$this->addInstance($this);
	}

	/**
	 * Adds an instance of an object. This can be usefull when you need to make
     * available instances which requires a specific configuration like the
     * ServerRequest object or Doctrine's EntityManager.
	 *
	 * @param object $instance
	 * @throws NotAnObjectException
	 * @throws \ReflectionException
	 */
	public function addInstance($instance):void
	{
		// checks the serivce
		if (!is_object($instance)) {
			throw new NotAnObjectException(gettype($instance), $this);
		}

		// adds the instance
		$reflectionClass = new \ReflectionClass($instance);
		$this->instances[$reflectionClass->getName()] = $instance;

		// maps the instance interfaces
		foreach ($reflectionClass->getInterfaces() as $interface) {
			$this->addAlias(
			    $reflectionClass->getName(),
                $interface->getName(),
                false
            );
		}

		// maps the instance parent classes
		$parent = $reflectionClass;
		while ($parent = $parent->getParentClass()) {
			$this->addAlias(
			    $reflectionClass->getName(),
                $parent->getName(),
                false
            );
		}
	}

	/**
	 * Adds an alias for a class of an interface. This is the way to tell the
     * service manager to use a specific class when it encounters an interface
     * as a type hint.
	 *
	 * @param string $class Class or interface name
	 * @param string $alias
	 * @param bool $replace
	 * @return bool
	 */
	public function addAlias(string $class, string $alias,
        bool $replace = true):bool
	{
		if ($replace === true || !isset($this->aliases[$alias])) {
			$this->aliases[$alias] = $class;
		}
		return false;
	}

    /**
     * Verifies if an alias exist for a class / interface.
     *
     * @param string $class
     * @return null|string
     */
	public function hasAlias(string $class):?string
    {
        return $this->aliases[$class] ?? null;
    }

	/**
	 * @param string $class
	 * @return null|string
	 */
	public function getAlias(string $class):?string
	{
		return $this->aliases[$class] ?? null;
	}

	/**
	 * Returns an instance. If the instance was used previously, returns
     * the previous one. If the instance was added using addInstance(),
     * returns the added instance.
	 *
	 * @param string $class
	 * @return object
	 * @throws ClassNotFoundException
	 * @throws InterfaceWithoutAliasException
	 * @throws NotAnObjectException
	 * @throws InstantiatorException
	 * @throws \ReflectionException
	 */
	public function getInstance(string $class)
	{
		// if there is an instance already available to the given class
		if (isset($this->instances[$class])) {
			return $this->instances[$class];
		}

		// if the class is an alias
		if ($alias = $this->getAlias($class)) {
			$class = $alias;
		}

		// checks if the class exists
		if (!class_exists($class)) {
			throw new ClassNotFoundException($class, $this);
		}

		// checks if the class is an interface
		$reflectionClass = new \ReflectionClass($class);
		if ($reflectionClass->isInterface()) {
			throw new InterfaceWithoutAliasException($class, $this);
		}

		// if the class was never added or instantiated, we instantiate it
		$instance = $this->newInstance($reflectionClass);
		$this->addInstance($instance);
		return $instance;
	}

    /**
     * Verifies the manager has an instance of an object.
     *
     * @param string $class
     * @return bool
     */
	public function hasInstance(string $class):bool
    {
        if (isset($this->instances[$class])) {
            return  true;
        }

        if ($alias = $this->getAlias($class)) {
            return $this->hasInstance($alias);
        }

        return false;
    }

	/**
	 * Alias of getInstance()
	 *
	 * @uses Instantiator::getInstance()
	 * @param string $class
	 * @return object
	 * @throws InstantiatorException
	 * @throws \ReflectionException
	 */
	public function __invoke(string $class)
	{
		return $this->getInstance($class);
	}

	/**
	 * @param \ReflectionClass $reflectionClass
	 * @return object
	 * @throws NewInstanceException
	 * @throws InstantiatorException
	 */
	private function newInstance(\ReflectionClass $reflectionClass)
	{
		try {
			return $reflectionClass->newInstanceArgs(
			    $this->getCustructorParams($reflectionClass)
            );
		}
		catch (InstantiatorException $exception) {
			throw $exception;
		}
		catch (\Throwable $exception) {
            throw new NewInstanceException($reflectionClass->getName(), $this);
		}
	}

	/**
	 * Returns the array of the constuctor parameters value for the given class.
	 *
	 * @param \ReflectionClass $reflectionClass
	 * @return array
	 * @throws ClassNotFoundException
	 * @throws InterfaceWithoutAliasException
	 * @throws NotAnObjectException
	 * @throws InstantiatorException
	 * @throws \ReflectionException
     * @throws ParamValueException
	 */
	private function getCustructorParams(\ReflectionClass $reflectionClass):array
	{
		$args = [];
		foreach ($reflectionClass->getMethod("__construct")->getParameters()
		         as $number => $reflectionParameter) {
			try {
				$args[] = $this->getCustructorParamValue($reflectionParameter);
			}
			catch (ParamTypeException $exception) {
                throw new ParamValueException(
                    $reflectionClass->getName(),
                    $reflectionParameter->getName(),
                    $number + 1,
                    $exception->getMessage(),
                    $this
                );
			}
		}
		return $args;
	}

	/**
	 * Returns the value of a constructor parameter.
	 *
	 * @param \ReflectionParameter $reflectionParameter
	 * @return mixed
	 * @throws ClassNotFoundException
	 * @throws InterfaceWithoutAliasException
	 * @throws NotAnObjectException
	 * @throws ParamTypeException
	 * @throws InstantiatorException
	 * @throws \ReflectionException
	 */
	private function getCustructorParamValue(\ReflectionParameter $reflectionParameter)
	{
		// if the param is required
		if (!$reflectionParameter->isOptional()) {
			// if the param type is not set
			if (!$reflectionParameter->hasType()) {
				throw new ParamTypeException(
					sprintf("param does not have a type hint"),
					$this
				);
			}

			// if the param type is not a class
			if ($reflectionParameter->getType()->isBuiltin()) {
				throw new ParamTypeException(
					sprintf("param type is not a class or an interface (type: %s)",
						$reflectionParameter->getType()->getName()),
					$this
				);
			}

			// instantiating the class
			return $this->getInstance($reflectionParameter->getType()->getName());
		}

		// optionnal param
		else {
			// if the param is optionnal returning it's default value
			return $reflectionParameter->getDefaultValue();
		}
	}
}