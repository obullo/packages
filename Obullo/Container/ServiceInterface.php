<?php

namespace Obullo\Container;

/**
 * Service Interface
 * 
 * @copyright 2009-2015 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface ServiceInterface
{
    /**
     * Set service parameters
     * 
     * @param array $params service configuration
     *
     * @return void
     */
    public function setParams(array $params);

    /**
     * Registry
     * 
     * @return void
     */
    public function register();
}