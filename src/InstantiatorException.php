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
// Time:     12:09
// Project:  ServicesManager
//
declare(strict_types=1);
namespace CodeInc\ServicesManager;
use Throwable;


/**
 * Class InstantiatorException
 *
 * @package CodeInc\ServicesManager
 * @author  Joan Fabrégat <joan@codeinc.fr>
 */
class InstantiatorException extends ServicesManagerException
{
    /**
     * @var Instantiator
     */
    private $instantiator;

    /**
     * InstantiatorException constructor.
     *
     * @param string $message
     * @param Instantiator $instantiator
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(string $message, Instantiator $instantiator,
        int $code = 0, ?Throwable $previous = null)
    {
        $this->instantiator = $instantiator;
        parent::__construct($message, $instantiator->getServiceManager(), $code, $previous);
    }

    /**
     * @return Instantiator
     */
    public function getInstantiator():Instantiator
    {
        return $this->instantiator;
    }
}