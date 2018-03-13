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
// Date:     13/03/2018
// Time:     10:28
// Project:  lib-servicemanager
//
declare(strict_types = 1);
namespace CodeInc\ServiceManager;
use CodeInc\ServiceManager\Exceptions\ClassNotFoundException;
use CodeInc\ServiceManager\Exceptions\NewInstanceException;
use CodeInc\ServiceManager\Exceptions\NotAnObjectException;
use CodeInc\ServiceManager\Exceptions\ParamValueException;


/**
 * Class Instantiator
 *
 * @package CodeInc\ServiceManager
 * @author Joan Fabrégat <joan@codeinc.fr>
 * @todo add object type hint when min compatibility >= 7.2
 */
class Instantiator
{
    /**
     * @var ServiceManager
     */
    private $serviceManager;

    /**
     * @var array
     */
    private $dependencies;

    /**
     * Instantiator constructor.
     *
     * @param ServiceManager $serviceManager
     * @param array|null $dependencies
     * @throws NotAnObjectException
     */
    public function __construct(ServiceManager $serviceManager,
        ?array $dependencies = null)
    {
        $this->serviceManager = $serviceManager;
        if ($dependencies) {
            $this->addDependencies($dependencies);
        }
    }

    /**
     * @return ServiceManager
     */
    public function getServiceManager():ServiceManager
    {
        return $this->serviceManager;
    }

    /**
     * Adds a dependency for the class constructor.
     *
     * @param $dependency
     * @throws NotAnObjectException
     */
    public function addDependency($dependency):void
    {
        if (!is_object($dependency)) {
            throw new NotAnObjectException($dependency, $this->serviceManager);
        }
        $this->dependencies[get_class($dependency)] = $dependency;
    }

    /**
     * @param array $dependencies
     * @throws NotAnObjectException
     */
    public function addDependencies(array $dependencies):void
    {
        foreach ($dependencies as $dependency) {
            $this->addDependency($dependency);
        }
    }

    /**
     * Returns the dependencies array.
     *
     * @return object[]
     */
    public function getDependencies():array
    {
        return $this->dependencies;
    }

    /**
     * Removes a dependency
     *
     * @param string $dependencyClass
     */
    public function removeDependency(string $dependencyClass):void
    {
        unset($this->dependencies[$dependencyClass]);
    }

    /**
     * Instantiate the class an returns the new instance.
     *
     * @param string $class
     * @return object
     * @throws NewInstanceException
     * @throws ClassNotFoundException
     */
    public function instantiate(string $class)
    {
        // checks if the class exists
        if (!class_exists($class)) {
            throw new ClassNotFoundException($class, $this->serviceManager);
        }

        // instantiate the class
        try {
            $class = new \ReflectionClass($class);
            if ($class->hasMethod("__construct")) {
                $args = [];
                foreach ($class->getMethod("__construct")->getParameters() as $number => $param) {
                    $args[] = $this->getCustructorParamValue($param, $class, $number + 1);
                }
                return $class->newInstanceArgs($args);
            }
            return $class->newInstance();
        }
        catch (\Throwable $exception) {
            throw new NewInstanceException(
                $class->getName(),
                $this->serviceManager,
                null, $exception
            );
        }
    }

    /**
     * Returns the value of a constructor parameter.
     *
     * @param \ReflectionParameter $param
     * @param \ReflectionClass $class
     * @param int $number
     * @return mixed
     * @throws Exceptions\ClassNotFoundException
     * @throws Exceptions\InterfaceWithoutAliasException
     * @throws Exceptions\NotAnObjectException
     * @throws Exceptions\ServiceManagerException
     * @throws ParamValueException
     * @throws \ReflectionException
     */
    private function getCustructorParamValue(\ReflectionParameter $param,
        \ReflectionClass $class, int $number)
    {
        // if the param is required
        if (!$param->isOptional()) {

            // if the param type is not set
            if (!$param->hasType()) {
                throw new ParamValueException(
                    $class->getName(),
                    $param->getName(),
                    $number,
                    "the parameter does not have a type hint",
                    $this->serviceManager
                );
            }

            // if the param type is not a class
            $paramClass = $param->getType()->getName();
            if ($param->getType()->isBuiltin()) {
                throw new ParamValueException(
                    $class->getName(),
                    $param->getName(),
                    $number,
                    sprintf("the parameter type is not a class or an interface (type: %s)",
                        $paramClass),
                    $this->serviceManager
                );
            }

            // if the parameter value is available among the local dependencies
            if (isset($this->dependencies[$paramClass])) {
                return $this->dependencies[$paramClass];
            }

            // else instantiating the class
            return $this->serviceManager->getService($paramClass);
        }

        // optionnal param
        else {
            // if the param is optionnal returning it's default value
            return $param->getDefaultValue();
        }
    }
}