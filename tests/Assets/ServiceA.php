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
 * Class ServiceA
 *
 * @package CodeInc\ServicesManager\Tests\Assets
 * @author  Joan Fabrégat <joan@codeinc.fr>
 */
class ServiceA
{
    /**
     * @param string $name
     * @return string
     */
    public function hello(string $name):string
    {
        return sprintf("Hello %s", $name);
    }
}