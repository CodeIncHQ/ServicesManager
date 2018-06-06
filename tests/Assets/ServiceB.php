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
// Time:     13:12
// Project:  ServicesManager
//
declare(strict_types=1);
namespace CodeInc\ServicesManager\Tests\Assets;

/**
 * Class ServiceB
 *
 * @package CodeInc\ServicesManager\Tests\Assets
 * @author  Joan Fabrégat <joan@codeinc.fr>
 */
class ServiceB
{
    /**
     * @var ServiceA
     */
    private $serviceA;

    /**
     * ServiceB constructor.
     *
     * @param ServiceA $serviceA
     */
    public function __construct(ServiceA $serviceA)
    {
        $this->serviceA = $serviceA;
    }

    /**
     * @return string
     */
    public function helloWorld():string
    {
        return $this->serviceA->hello("World");
    }
}