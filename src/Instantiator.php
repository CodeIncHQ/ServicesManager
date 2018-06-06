<?php
//
// +---------------------------------------------------------------------+
// | CODE INC. SOURCE CODE                                               |
// +---------------------------------------------------------------------+
// | Copyright (c) 2018 - Code Inc. SAS - All Rights Reserved.           |
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
// Date:     06/06/2018
// Time:     12:02
// Project:  ServicesManager
//
declare(strict_types=1);
namespace CodeInc\ServicesManager;


/**
 * Class Instantiator
 *
 * @package CodeInc\ServicesManager
 * @author  Joan Fabrégat <joan@codeinc.fr>
 * @todo add object type hint when min compatibility >= 7.2
 */
class Instantiator
{
    /** @var ServicesManager */
    private $serviceManager;

    /** @var string */
    private $class;

    /** @var \ReflectionClass */
    private $reflectionClass;

    /** @var object[] */
    private $constructorDependencies = [];

    /**
     * Instantiator constructor.
     *
     * @param ServicesManager $servicesManager
     * @param string $class
     * @param object[] $constructorDependencies
     * @throws InstantiatorException
     */
    public function __construct(ServicesManager $servicesManager, string $class,
        ?array $constructorDependencies = null)
    {
        try {
            if (!class_exists($class)) {
                throw new InstantiatorException(
                    sprintf("The class '%s' does not exist", $class),
                    $this
                );
            }
            $this->serviceManager = $servicesManager;
            $this->class = $class;
            $this->reflectionClass = new \ReflectionClass($class);
            if ($constructorDependencies) {
                $this->setConstructorDependencies($constructorDependencies);
            }
        }
        catch (\Throwable $exception) {
            throw new InstantiatorException("Error while loading the Instantiator",
                $this, 0, $exception);
        }
    }

    /**
     * @param array $constructorDependencies
     * @throws InstantiatorException
     */
    public function setConstructorDependencies(array $constructorDependencies):void
    {
        $i = 0;
        foreach ($constructorDependencies as $dependency) {
            if (!is_object($dependency)) {
                throw new InstantiatorException(
                    sprintf("The dependency #%s is not an object (type '%s')",
                        $i, gettype($dependency)),
                    $this
                );
            }
            $this->constructorDependencies[] = $dependency;
            $i++;
        }
    }

    /**
     * Adds a constructor dependency.
     *
     * @param $dependency
     * @throws InstantiatorException
     */
    public function addConstructorDependency($dependency):void
    {
        if (!is_object($dependency)) {
            throw new InstantiatorException(
                sprintf("The dependency is not an object (type '%s')",
                    gettype($dependency)),
                $this
            );
        }
        $this->constructorDependencies[] = $dependency;
    }

    /**
     * @return object[]
     */
    public function getConstructorDependencies():array
    {
        return $this->constructorDependencies;
    }

    /**
     * @return ServicesManager
     */
    public function getServiceManager():ServicesManager
    {
        return $this->serviceManager;
    }

    /**
     * Instantiate the class.
     *
     * @return object
     * @throws InstantiatorException
     */
    public function instantiate()
    {
        try {
            if (!$this->reflectionClass->isInstantiable()) {
                throw new InstantiatorException("The class is not instantiable", $this);
            }
            if ($this->reflectionClass->hasMethod("__construct")) {
                return $this->reflectionClass->newInstanceArgs(
                    $this->getConstructorArgs()
                );
            }
            else {
                return $this->reflectionClass->newInstance();
            }
        }
        catch (\Throwable $exception) {
            throw new InstantiatorException(
                sprintf("Error while instantiating the class '%s'", $this->class),
                $this, 0, $exception
            );
        }
    }

    /**
     * Returns the class's constructor args.
     *
     * @return array
     * @throws InstantiatorException
     */
    private function getConstructorArgs():array
    {
        try {
            $constructorArgs = [];
            foreach ($this->reflectionClass->getMethod("__construct")->getParameters() as $constructorParam) {
                // if the param is required
                if (!$constructorParam->isOptional()) {
                    // if the param type is not set
                    if (!$constructorParam->hasType()) {
                        throw new InstantiatorException(
                            sprintf("The '%s' parameter (#%s) does not have a type hint",
                                $constructorParam->getName(),
                                $constructorParam->getPosition() + 1),
                            $this
                        );
                    }

                    // if the param type is not a class
                    if ($constructorParam->getType()->isBuiltin()) {
                        throw new InstantiatorException(
                            sprintf("The '%s' parameter (#%s) is not a class or an interface (type '%s')",
                                $constructorParam->getName(),
                                $constructorParam->getPosition() + 1,
                                $constructorParam->getType()->getName()),
                            $this
                        );
                    }

                    // if the parameter value is available among the local dependencies
                    $paramClass = $constructorParam->getClass()->getName();
                    if ($dependency = $this->searchDependencies($paramClass)) {
                        $constructorArgs[] = $dependency;
                    }

                    // else instantiating the class
                    else {
                        $constructorArgs[] = $this->serviceManager->getService($paramClass);
                    }
                }

                // optionnal param
                else {
                    // if the param is optionnal returning it's default value
                    $constructorArgs[] = $constructorParam->isDefaultValueAvailable()
                        ? $constructorParam->getDefaultValue()
                        : null;
                }
            }
            return $constructorArgs;
        }
        catch (\Throwable $exception) {
            throw new InstantiatorException(
                sprintf("Unable to assemble the %s method parameters.",
                    $this->class.'::__construct()'),
                $this, 0, $exception
            );
        }
    }

    /**
     * Searches for a class within a dependencies array.
     *
     * @param string $class
     * @return object|null
     */
    private function searchDependencies(string $class)
    {
        foreach ($this->constructorDependencies as $dependency) {
            if (is_object($dependency) && $dependency instanceof $class) {
                return $dependency;
            }
        }
        return null;
    }
}