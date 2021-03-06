<?php

namespace Obullo\Utils\Error;

use Exception;
use Obullo\Utils\File;
use Psr\Log\LoggerInterface as Logger;

/**
 * Log all application errors
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Log
{
    /**
     * Logger
     * 
     * @var object
     */
    protected $logger;

    /**
     * Constructor
     * 
     * @param Logger $logger logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Write errors
     * 
     * @param Exception $e exception object
     * 
     * @return void
     */
    public function message(Exception $e)
    {        
        if ($this->logger instanceof Logger) {

            $this->logger->withName('system')->error(
                $e->getMessage(),
                [
                    'file' => File::getSecurePath($e->getFile()),
                    'line' => $e->getLine()
                ]
            );
        }
    }
}

