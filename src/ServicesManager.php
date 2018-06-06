<?php
//
// +---------------------------------------------------------------------+
// | CODE INC. SOURCE CODE                                               |
// +---------------------------------------------------------------------+
// | Copyright (c) 2017-2018 - Code Inc. SAS - All Rights Reserved.      |
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


/**
 * Class ServicesManager
 *
 * @package CodeInc\ServicesManager
 * @author Joan Fabrégat <joan@codeinc.fr>
 * @todo add object type hint when min compatibility >= 7.2
 */
class ServicesManager
{
    /**
     * Instantiated objects stack.
     *
     * @see ServicesManager::registerService()
     * @see ServicesManager::getService()
     * @var object[]
     */
    private $services = [];

    /**
     * ServicesManager constructor.
     *
     * @throws InstantiatorException
     * @throws ServicesManagerException
     */
    public function __construct()
    {
        $this->registerService($this);
    }

    /**
     * Adds a service.
     *
     * @param object|string $service Instantiated service or service class.
     * @param array $dependencies
     * @param bool $replace
     * @throws InstantiatorException
     * @throws ServicesManagerException
     */
    public function registerService($service, array $dependencies = [], bool $replace = false):void
    {
        if (is_object($service)) {
            $class = get_class($service);
        }
        else {
            $class = $service;
            $service = $this->instantiate($service, $dependencies);
        }

        if (array_key_exists($class, $this->services) && !$replace) {
            throw new ServicesManagerException(
                sprintf("The service '%s' is already registered", $class),
                $this
            );
        }
        $this->services[$class] = $service;
    }

    /**
     * Returns the registered services.
     *
     * @return object[]
     */
    public function getRegisteredServices():array
    {
        return $this->services;
    }

    /**
     * Returns a service instance
     *
     * @param string $serviceClass
     * @param array $dependencies
     * @return object
     * @throws ServicesManagerException
     */
    public function getService(string $serviceClass, array $dependencies = [])
    {
        // checks if the class exists
        if (!class_exists($serviceClass)) {
            throw new ServicesManagerException(
                sprintf("The service '%s' does not exist", $serviceClass),
                $this
            );
        }

        // if there is an instance already available
        if (isset($this->services[$serviceClass])) {
            return $this->services[$serviceClass];
        }

        // if a service if a instance of the request class
        foreach ($this->services as $service) {
            if ($service instanceof $serviceClass) {
                return $service;
            }
        }

        // if the class was never added or instantiated, we instantiate it
        $service = $this->instantiate($serviceClass, $dependencies);
        $this->registerService($service);
        return $service;
    }

    /**
     * Alias of getService()
     *
     * @param string $serviceClass
     * @return object
     * @throws ServicesManagerException
     */
    public function __invoke(string $serviceClass)
    {
        return $this->getService($serviceClass);
    }

    /**
     * Verifies the manager has a service.
     *
     * @param string|object $service
     * @return bool
     */
    public function hasService($service):bool
    {
        // if the class is an object
        if (is_object($service)) {
            $service = get_class($service);
        }

        if (isset($this->services[$service])) {
            return true;
        }

        foreach ($this->services as $service) {
            if ($service instanceof $service) {
                return true;
            }
        }

        return false;
    }

    /**
     * Instantiate any class which requires services.
     *
     * @param string $class
     * @param object[] $dependencies
     * @return object
     * @throws InstantiatorException|ServicesManagerException
     */
    public function instantiate(string $class, array $dependencies = [])
    {
        return (new Instantiator($this, $class, $dependencies))->instantiate();
    }
}