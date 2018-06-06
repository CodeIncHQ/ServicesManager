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
// Time:     13:13
// Project:  ServicesManager
//
declare(strict_types=1);
namespace CodeInc\ServicesManager\Tests;
use CodeInc\ServicesManager\Instantiator;
use CodeInc\ServicesManager\ServicesManager;
use CodeInc\ServicesManager\Tests\Assets\ServiceA;
use CodeInc\ServicesManager\Tests\Assets\ServiceB;
use PHPUnit\Framework\TestCase;


/**
 * Class TestServiceManager
 *
 * @uses ServicesManager
 * @package CodeInc\ServicesManager\Tests
 * @author  Joan Fabrégat <joan@codeinc.fr>
 */
class TestServiceManager extends TestCase
{
    /**
     * @return ServicesManager
     * @throws \CodeInc\ServicesManager\InstantiatorException
     * @throws \CodeInc\ServicesManager\ServicesManagerException
     */
    public function testServiceManager():ServicesManager
    {
        $servicesManager = new ServicesManager();
        self::assertTrue($servicesManager->hasService(ServicesManager::class));
        self::assertInstanceOf(ServicesManager::class,
            $servicesManager->getService(ServicesManager::class));
        self::assertInstanceOf(ServicesManager::class,
            $servicesManager(ServicesManager::class));
        return $servicesManager;
    }

    /**
     * @depends testServiceManager
     * @param ServicesManager $servicesManager
     * @throws \CodeInc\ServicesManager\InstantiatorException
     */
    public function testInstantiator(ServicesManager $servicesManager):void
    {
        $instantiator = new Instantiator($servicesManager, ServiceA::class);
        /** @var ServiceA $serviceA */
        $serviceA = $instantiator->instantiate();
        self::assertInstanceOf(ServiceA::class, $serviceA);
    }

    /**
     * @depends testServiceManager
     * @param ServicesManager $servicesManager
     * @throws \CodeInc\ServicesManager\InstantiatorException
     */
    public function testInstantiatorWithDependency(ServicesManager $servicesManager):void
    {
        $instantiator = new Instantiator($servicesManager, ServiceB::class, [new ServiceA()]);
        /** @var ServiceB $serviceB */
        $serviceB = $instantiator->instantiate();
        self::assertInstanceOf(ServiceB::class, $serviceB);
        self::assertEquals("Hello World", $serviceB->helloWorld());
    }

    /**
     * @depends testServiceManager
     * @param ServicesManager $servicesManager
     * @throws \CodeInc\ServicesManager\InstantiatorException
     * @throws \CodeInc\ServicesManager\ServicesManagerException
     */
    public function testInstantiatorWithService(ServicesManager $servicesManager):void
    {
        $servicesManager = clone $servicesManager;
        $servicesManager->registerService(new ServiceA());
        $instantiator = new Instantiator($servicesManager, ServiceB::class);
        /** @var ServiceB $serviceB */
        $serviceB = $instantiator->instantiate();
        self::assertInstanceOf(ServiceB::class, $serviceB);
        self::assertEquals("Hello World", $serviceB->helloWorld());
    }

    /**
     * @depends testServiceManager
     * @param ServicesManager $servicesManager
     * @throws \CodeInc\ServicesManager\InstantiatorException
     * @throws \CodeInc\ServicesManager\ServicesManagerException
     */
    public function testGetService(ServicesManager $servicesManager):void
    {
        $servicesManager = clone $servicesManager;
        $servicesManager->registerService(new ServiceA());

        /** @var ServiceB $serviceB */
        $serviceB = $servicesManager->getService(ServiceB::class);
        self::assertInstanceOf(ServiceB::class, $serviceB);
        self::assertEquals("Hello World", $serviceB->helloWorld());
    }
}