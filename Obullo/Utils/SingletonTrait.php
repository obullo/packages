<?php

namespace Obullo\Utils;

use RuntimeException;
use League\Container\ContainerInterface as Container;

trait SingletonTrait
{
    protected static $instance = null;  // Presence of a static member variable

    /**
     * Returns the singleton instance of this class.
     *
     * @param object $container Container
     * 
     * @return singleton instance.
     */
    public static function getInstance(Container $container)
    {
        if (null === self::$instance) {
            self::$instance = new static($container);
        }
        return self::$instance;
    }

    /**
     * Disable clone
     * 
     * @return void
     */
    public function __clone()
    {
        throw new RuntimeException(
            sprintf('Cloning %s is not allowed.', __CLASS__)
        );
    }
    
    /**
     * Disable unserialize
     *
     * @return void
     */
    public function __wakeup()
    {
        throw new RuntimeException(
            sprintf('Unserializing %s is not allowed.', __CLASS__)
        );
    }
}