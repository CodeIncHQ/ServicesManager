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
// Time:     18:05
// Project:  lib-instantiator
//
declare(strict_types = 1);
namespace CodeInc\Instantiator\Exceptions;
use CodeInc\Instantiator\Instantiator;
use Throwable;


/**
 * Class ParamValueException
 *
 * @package CodeInc\ServiceManager\Exceptions
 * @author Joan Fabrégat <joan@codeinc.fr>
 */
class ParamValueException extends InstantiatorException
{
    /**
     * ParamValueException constructor.
     *
     * @param string $className
     * @param string $paramName
     * @param int $paramNumber
     * @param string $message
     * @param Instantiator $instantiator
     * @param int|null $code
     * @param null|Throwable $previous
     */
    public function __construct(string $className, string $paramName,
        int $paramNumber, string $message, Instantiator $instantiator,
        ?int $code = null, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                "Error while preparing the value of the parameter %s (#%s) "
                ."of %s::__construct(): %s",
                "\${$paramName}", $paramNumber, $className, $message
            ),
            $instantiator,
            $code,
            $previous
        );
    }
}