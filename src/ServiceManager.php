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
// Project:  lib-servicemanager
//
declare(strict_types = 1);
namespace CodeInc\ServiceManager;
use CodeInc\ServiceManager\Exceptions\InterfaceWithoutAliasException;
use CodeInc\ServiceManager\Exceptions\NotAnObjectException;
use CodeInc\ServiceManager\Exceptions\NotAServiceException;
use CodeInc\ServiceManager\Exceptions\ServiceManagerException;
use CodeInc\ServiceManager\Exceptions\ClassNotFoundException;


/**
 * Class ServiceManager
 *
 * @package CodeInc\ServiceManager
 * @author Joan Fabrégat <joan@codeinc.fr>
 * @todo add object type hint when min compatibility >= 7.2
 */
class ServiceManager implements ServiceInterface
{
    /**
     * Instantiated objects stack.
     *
     * @see ServiceManager::addService()
     * @see ServiceManager::getService()
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
     * @var Instantiator
     */
    private $internalInstantiator;

    /**
     * ServiceManager constructor.
     *
     * @throws ServiceManagerException
     * @throws \ReflectionException
     */
    public function __construct()
    {
        $this->addService($this);
        $this->internalInstantiator = new Instantiator($this);
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
    public function addService($instance):void
    {
        // checks the serivce
        if (!is_object($instance)) {
            throw new NotAnObjectException(gettype($instance), $this);
        }

        // adds the instance
        $reflectionClass = new \ReflectionClass($instance);
        $this->services[$reflectionClass->getName()] = $instance;

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
     * @return mixed
     * @throws ClassNotFoundException
     * @throws InterfaceWithoutAliasException
     * @throws NotAnObjectException
     * @throws ServiceManagerException
     * @throws \ReflectionException
     */
    public function getService(string $class)
    {
        // if the class is an alias
        if ($alias = $this->getAlias($class)) {
            $class = $alias;
        }

        // if there is an instance already available to the given class
        if (isset($this->services[$class])) {
            return $this->services[$class];
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
        if (is_subclass_of($class, ServiceInterface::class)) {
            throw new NotAServiceException($class, $this);
        }

        // if the class was never added or instantiated, we instantiate it
        $instance = $this->internalInstantiator->instantiate($class);
        $this->addService($instance);
        return $instance;
    }

    /**
     * Alias of getInstance()
     *
     * @uses ServiceManager::getService()
     * @param string $class
     * @return object
     * @throws ServiceManagerException
     * @throws \ReflectionException
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
        if (is_object($class)) {
            $class = get_class($class);
        }

        if (isset($this->services[$class])) {
            return  true;
        }

        if ($alias = $this->getAlias($class)) {
            return $this->hasServiceInstance($alias);
        }

        return false;
    }

    /**
     * Returns a new instance of the instantiator.
     *
     * @return Instantiator
     */
    public function getInstantiator():Instantiator
    {
        return new Instantiator($this);
    }
}