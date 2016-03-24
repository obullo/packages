<?php

namespace Obullo\Application;

use Closure;
use Exception;
use ErrorException;
use RuntimeException;
use ReflectionFunction;
use Obullo\Error\Debug;
use Obullo\Http\Controller;
use Interop\Container\ContainerInterface as Container;

/**
 * Application
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class Application implements ApplicationInterface
{
    const VERSION = '1.0rc1';

    protected $container;
    protected $fatalError;
    protected $exceptions = array();

    /**
     * Constructor
     * 
     * @param object $container container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    
    /**
     * Returns to detected environment
     * 
     * @return string
     */
    public function getEnv()
    {
        return $this->container->get('env')->getValue();
    }

    /**
     * Returns to current version of Obullo
     * 
     * @return string
     */
    public function getVersion()
    {
        return static::VERSION;
    }

    /**
     * Returns to container object
     * 
     * @return string
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Call controller methods from view files ( View files $this->method(); support ).
     * 
     * @param string $method    called method
     * @param array  $arguments called arguments
     * 
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if ($method == '__invoke') {
            return;
        }
        if (method_exists(Controller::$instance, $method)) {
            return Controller::$instance->$method($arguments);
        }
    }

    /**
     * Container & controller proxy
     * 
     * @param string $key application object
     * 
     * @return object
     */
    public function __get($key)
    {
        $appCid = 'app.'.$key;
        if ($this->container->has($appCid) ) {
            return $this->container->get($appCid);
        }
        if (class_exists('Controller', false) && Controller::$instance != null) {
            return Controller::$instance->{$key};
        }
        return $this->container->get($key);
    }

}