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
// Project:  ServicesManager
//
declare(strict_types = 1);
namespace CodeInc\ServicesManager;
use CodeInc\ServicesManager\Exceptions\InterfaceWithoutAliasException;
use CodeInc\ServicesManager\Exceptions\NewInstanceException;
use CodeInc\ServicesManager\Exceptions\NotAnObjectException;
use CodeInc\ServicesManager\Exceptions\NotAServiceException;
use CodeInc\ServicesManager\Exceptions\ParamValueException;
use CodeInc\ServicesManager\Exceptions\ServicesManagerException;
use CodeInc\ServicesManager\Exceptions\ClassNotFoundException;


/**
 * Class ServicesManager
 *
 * @package CodeInc\ServicesManager
 * @author Joan Fabrégat <joan@codeinc.fr>
 * @todo add object type hint when min compatibility >= 7.2
 */
class ServicesManager implements ServiceInterface
{
    /**
     * Instantiated objects stack.
     *
     * @see ServicesManager::addService()
     * @see ServicesManager::getService()
     * @var object[]
     */
    private $services = [];

    /**
     * Services aliases (other classes or interfaces)
     *
     * @see ServicesManager::addAlias()
     * @var string[]
     */
    private $aliases = [];

    /**
     * ServicesManager constructor.
     *
     * @throws ServicesManagerException
     */
    public function __construct()
    {
        $this->addService($this);
    }

    /**
     * Adds an instance of an object. This can be usefull when you need to make
     * available instances which requires a specific configuration like the
     * ServerRequest object or Doctrine's EntityManager.
     *
     * @param object $instance
     * @throws NotAnObjectException
     */
    public function addService($instance):void
    {
        // checks the serivce
        if (!is_object($instance)) {
            throw new NotAnObjectException(gettype($instance), $this);
        }

        // adds the instance
        $this->services[get_class($instance)] = $instance;

        // maps the class aliases (instances and parents)
//        foreach ($this->getClassAliases($reflectionClass) as $alias) {
//            $this->addAlias(
//                $reflectionClass->getName(),
//                $alias->getName(),
//                false
//            );
//        }
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
     * @return bool
     */
    public function hasAlias(string $class):bool
    {
        return isset($this->aliases[$class]);
    }

    /**
     * Returns an alias target or null if not set.
     *
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
     * @param array|null $dependencies
     * @return mixed
     * @throws ClassNotFoundException
     * @throws InterfaceWithoutAliasException
     * @throws NotAnObjectException
     * @throws ServicesManagerException
     */
    public function getService(string $class, ?array $dependencies = null)
    {
        // if the class is an alias
        if ($alias = $this->getAlias($class)) {
            $class = $alias;
        }

        // if there is an instance already available
        if ($matchClass = $this->hasMatch($class, array_keys($this->services))) {
            return $this->services[$matchClass];
        }

        // checks if the class is an interface
        if (interface_exists($class)) {
            throw new InterfaceWithoutAliasException($class, $this);
        }

        // checks if the class exists
        if (!class_exists($class)) {
            throw new ClassNotFoundException($class, $this);
        }

        // checks if the class is a service
        if (!is_subclass_of($class, ServiceInterface::class)) {
            throw new NotAServiceException($class, $this);
        }

        // if the class was never added or instantiated, we instantiate it
        $service = $this->instantiate($class, $dependencies);
        $this->addService($service);
        return $service;
    }

    /**
     * Checks if a class is either a member of an array, a subclass of a
     * member of an array.
     *
     * @param string $class
     * @param array $classes
     * @return null|string
     */
    private function hasMatch(string $class, array $classes):?string
    {
        if (in_array($class, $classes)) {
            return $class;
        }
        foreach ($classes as $item) {
            if (is_subclass_of($item, $class)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * Alias of getService()
     *
     * @uses ServicesManager::getService()
     * @param string $class
     * @return object
     * @throws ServicesManagerException
     */
    public function __invoke(string $class)
    {
        return $this->getService($class);
    }

    /**
     * Verifies the manager has an instance of an object.
     *
     * @param string|object $class
     * @return bool
     */
    public function hasServiceInstance($class):bool
    {
        // if the class is an object
        if (is_object($class)) {
            $class = get_class($class);
        }

        // if the class is an alias
        if ($alias = $this->getAlias($class)) {
            $class = $alias;
        }

        return isset($this->services[$class]);
    }

    /**
     * Instantiate any class which requires services.
     *
     * @param string $class
     * @param object[] $dependencies
     * @return object
     * @throws NewInstanceException
     * @throws ClassNotFoundException
     */
    public function instantiate(string $class, ?array $dependencies = null)
    {
        // checks if the class exists
        if (!class_exists($class)) {
            throw new ClassNotFoundException($class, $this);
        }

        // instantiate the class
        try {
            $class = new \ReflectionClass($class);
            if (!$class->isInstantiable()) {
                throw new NotInstantiableException($class, $this);
            }
            if ($class->hasMethod("__construct")) {
                $args = [];
                foreach ($class->getMethod("__construct")->getParameters() as $param) {
                    // if the param is required
                    if (!$param->isOptional()) {
                        // if the param type is not set
                        if (!$param->hasType()) {
                            throw new ParamValueException(
                                $class->getName(), $param->getName(), $param->getPosition() + 1,
                                "the parameter does not have a type hint", $this
                            );
                        }

                        // if the param type is not a class
                        if ($param->getType()->isBuiltin()) {
                            throw new ParamValueException(
                                $class->getName(), $param->getName(), $param->getPosition() + 1,
                                sprintf("the parameter type is not a class or an interface (type: %s)",
                                    $param->getType()->getName()), $this
                            );
                        }

                        // if the parameter value is available among the local dependencies
                        $paramClass = $param->getClass()->getName();
                        if ($dependencies
                            && $matchParamClass = $this->hasMatch($paramClass, $dependencies)) {
                            $args[] = $dependencies[$matchParamClass];
                        }

                        // else instantiating the class
                        else {
                            $args[] = $this->getService($paramClass);
                        }
                    }

                    // optionnal param
                    else {
                        // if the param is optionnal returning it's default value
                        $args[] = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
                    }
                }
                return $class->newInstanceArgs($args);
            }
            else {
                return $class->newInstance();
            }
        }
        catch (\Throwable $exception) {
            throw new NewInstanceException(
                $class->getName(),
                $this,
                null, $exception
            );
        }
    }
}