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
// Project:  intranet
//
declare(strict_types = 1);
namespace CodeInc\ServiceManager;
use CodeInc\ServiceManager\Exceptions\InterfaceWithoutAliasException;
use CodeInc\ServiceManager\Exceptions\NewInstanceException;
use CodeInc\ServiceManager\Exceptions\NotAnObjectException;
use CodeInc\ServiceManager\Exceptions\NotAServiceException;
use CodeInc\ServiceManager\Exceptions\ParamValueException;
use CodeInc\ServiceManager\Exceptions\ServiceManagerException;
use CodeInc\ServiceManager\Exceptions\ParamTypeException;
use CodeInc\ServiceManager\Exceptions\ClassNotFoundException;


/**
 * Class Instantiator
 *
 * @package Instantiator
 * @author Joan Fabrégat <joan@codeinc.fr>
 * @todo add object type hint when min compatibility >= 7.2
 */
class ServiceManager implements ServiceInterface {
	/**
	 * Instantiated objects stack.
	 *
	 * @see ServiceManager::addInstance()
	 * @see ServiceManager::getInstance()
	 * @var object[]
	 */
	private $services = [];

	/**
	 * Services aliases (other classes or interfaces)
	 *
	 * @see ServiceManager::addAlias()
	 * @var string[]
	 */
	private $aliases = [];

	/**
	 * ServiceManager constructor.
	 *
	 * @throws ServiceManagerException
	 * @throws \ReflectionException
	 */
	public function __construct()
	{
		$this->addInstance($this);
	}

	/**
	 * Adds an instantiated service. This can be usefull when you need to make
     * available services which requires a specific configuration. It also allows
     * to make available objects which are not technically services like the
     * ServerRequest object or Doctrine's EntityManager.
	 *
	 * @param ServiceInterface|object $service
	 * @throws NotAnObjectException
	 * @throws \ReflectionException
	 */
	public function addInstance($service):void
	{
		// checks the serivce
		if (!is_object($service)) {
			throw new NotAnObjectException(gettype($service), $this);
		}

		// adds the service
		$reflectionClass = new \ReflectionClass($service);
		$this->services[$reflectionClass->getName()] = $service;

		// maps the service interfaces
		foreach ($reflectionClass->getInterfaces() as $interface) {
			$this->addAlias(
			    $reflectionClass->getName(),
                $interface->getName(),
                false
            );
		}

		// maps the service parent classes
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
	 * @param string $serviceClass
	 * @param string $alias
	 * @param bool $replace
	 * @return bool
	 */
	public function addAlias(string $serviceClass, string $alias,
        bool $replace = true):bool
	{
		if ($replace === true || !isset($this->aliases[$alias])) {
			$this->aliases[$alias] = $serviceClass;
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
	 * Returns a service instance. If the service was used previously, returns
     * the previous instance. If the service was added using addService(),
     * returns the added instance. If the service was never called, instantiate
     * the service and all of its requirements.
	 *
	 * @param string $serviceClass
	 * @return object
	 * @throws ClassNotFoundException
	 * @throws InterfaceWithoutAliasException
	 * @throws NotAnObjectException
	 * @throws ServiceManagerException
	 * @throws \ReflectionException
	 */
	public function getInstance(string $serviceClass)
	{
		// if there is an instance already available to the given class
		if (isset($this->services[$serviceClass])) {
			return $this->services[$serviceClass];
		}

		// if the class is an alias
		if ($alias = $this->getAlias($serviceClass)) {
			$serviceClass = $alias;
		}

		// checks if the class exists
		if (!class_exists($serviceClass)) {
			throw new ClassNotFoundException($serviceClass, $this);
		}

		// checks if the service is an interface
		$reflectionClass = new \ReflectionClass($serviceClass);
		if ($reflectionClass->isInterface()) {
			throw new InterfaceWithoutAliasException($serviceClass, $this);
		}

		// if the class was never added or instantiated, we instantiate it
		$service = $this->newInstance($reflectionClass);
		$this->addInstance($service);
		return $service;
	}

    /**
     * Verifies the manager has an instance of a service.
     *
     * @param string $serviceClass
     * @return bool
     */
	public function hasInstance(string $serviceClass):bool
    {
        if (isset($this->services[$serviceClass])) {
            return  true;
        }

        if ($alias = $this->getAlias($serviceClass)) {
            return $this->hasInstance($alias);
        }

        return false;
    }

	/**
	 * Alias of getService()
	 *
	 * @uses ServiceManager::getInstance()
	 * @param string $serviceClass
	 * @return object
	 * @throws ServiceManagerException
	 * @throws \ReflectionException
	 */
	public function __invoke(string $serviceClass)
	{
		return $this->getInstance($serviceClass);
	}

	/**
	 * @param \ReflectionClass $reflectionClass
	 * @return object
	 * @throws NewInstanceException
	 * @throws ServiceManagerException
	 */
	private function newInstance(\ReflectionClass $reflectionClass)
	{
		try {
			return $reflectionClass->newInstanceArgs(
			    $this->getCustructorParams($reflectionClass)
            );
		}
		catch (ServiceManagerException $exception) {
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
	 * @throws ServiceManagerException
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
	 * @throws ServiceManagerException
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

            // if the class is a not service
            if (is_subclass_of($reflectionParameter->getType()->getName(),
                ServiceInterface::class)) {
                throw new NotAServiceException(
                    $reflectionParameter->getType()->getName(),
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