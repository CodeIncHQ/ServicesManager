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
// Time:     10:24
// Project:  ServicesManager
//
declare(strict_types = 1);
namespace CodeInc\ServicesManager\Exceptions;
use CodeInc\ServicesManager\ServiceInterface;
use CodeInc\ServicesManager\ServicesManager;
use Throwable;


/**
 * Class NotAServiceException
 *
 * @package CodeInc\ServicesManager\Exceptions
 * @author Joan Fabrégat <joan@codeinc.fr>
 */
class NotAServiceException extends ServicesManagerException
{
    /**
     * NotAServiceException constructor.
     *
     * @param string $class
     * @param ServicesManager $servicesManager
     * @param int|null $code
     * @param null|Throwable $previous
     */
    public function __construct(string $class, ServicesManager $servicesManager,
        ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf("The class %s is not a service. All services must implement %s.",
                $class, ServiceInterface::class),
            $servicesManager, $code, $previous);
    }
}