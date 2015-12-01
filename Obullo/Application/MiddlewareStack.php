<?php

namespace Obullo\Application;

use RuntimeException;

/**
 * Middleware stack
 * 
 * @author    Obullo Framework <obulloframework@gmail.com>
 * @copyright 2009-2015 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
class MiddlewareStack implements MiddlewareStackInterface
{
    /**
     * Count
     * 
     * @var integer
     */
    protected $count;

    /**
     * Names
     * 
     * @var array
     */
    protected $names = array();

    /**
     * Middleware stack
     * 
     * @var array
     */
    protected $queue = array();

    /**
     * Registered middlewares
     * 
     * @var array
     */
    protected $registered = array();

    /**
     * Constructor
     * 
     * @param Obullo\Container\Dependency $dependency object 
     */
    public function __construct($dependency)
    {
        $this->dependency = $dependency;
    }

    /**
     * Register application middlewares
     * 
     * @param array $array middlewares
     * 
     * @return object Middleware
     */
    public function register(array $array)
    {
        $this->registered = $array;
        return $this;
    }

    /**
     * Check given middleware is registered
     * 
     * @param string $name middleware
     * 
     * @return boolean
     */
    public function has($name)
    {
        if (isset($this->registered[$name])) {
            return true;
        }
        return false;
    }

    /**
     * Add middleware
     * 
     * @param string|array $name middleware key
     * 
     * @return object Middleware
     */
    public function add($name)
    {
        if (is_string($name)) {
            return $this->queueMiddleware($name);
        } elseif (is_array($name)) { 
            foreach ($name as $key) {
                $this->queueMiddleware($key);
            }
        }
        return $this;
    }

    /**
     * Check middleware is active
     * 
     * @param string $name middleware name
     * 
     * @return boolean
     */
    public function active($name)
    {
        $this->validateMiddleware($name);

        if (isset($this->names[$name]) 
            && isset($this->queue[$this->names[$name]]) 
            && $this->getClassNameByIndex($this->names[$name]) == $name
        ) {
            return true;
        }
        return false;
    }

    /**
     * Returns to middleware object to inject parameters
     * 
     * @param string $name middleware
     * 
     * @return object
     */
    public function get($name)
    {
        $this->validateMiddleware($name);
        $index = $this->names[$name];
        return $this->queue[$index];
    }

    /**
     * Get class name without namespace using explode method
     * 
     * @param integer $index number
     * 
     * @return string class name without namespace
     */
    protected function getClassNameByIndex($index)
    {
        $class = get_class($this->queue[$index]);
        $exp = explode("\\", $class);
        return end($exp);
    }

    /**
     * Resolve middleware
     * 
     * @param string $name middleware key
     * 
     * @return object mixed
     */
    protected function queueMiddleware($name)
    {
        ++$this->count;
        $this->validateMiddleware($name);
        $Class = $this->registered[$name];
        $this->names[$name] = $this->count;

        return $this->queue[$this->count] = $this->dependency->resolveDependencies($Class);  // Store middlewares
    }

    /**
     * Removes middleware
     * 
     * @param string|array $name middleware key
     * 
     * @return void
     */
    public function remove($name)
    {
        if (is_string($name)) {
            $this->validateMiddleware($name);

            if (! isset($this->names[$name])) {
                throw new RuntimeException(
                    sprintf(
                        'Middleware "%s" is not available',
                        $name
                    )
                );
            }
            $index = $this->names[$name];
            unset($this->queue[$index], $this->names[$name]);
        }
        if (is_array($name)) {
            foreach ($name as $key) {
                $this->remove($key);
            }
        }
    }

    /**
     * Validate middleware
     * 
     * @param string $name middleware
     * 
     * @return void
     */
    protected function validateMiddleware($name)
    {
        if (! isset($this->registered[$name])) {
            throw new RuntimeException(
                sprintf(
                    'Middleware "%s" is not registered in middlewares.php',
                    $name
                )
            );
        }
    }

    /**
     * Returns to middleware queue
     * 
     * @return array
     */
    public function getQueue()
    {
        return array_values($this->queue);
    }

    /**
     * Returns to all middleware names
     * 
     * @return array
     */
    public function getNames()
    {
        return array_keys($this->names);
    }

    /**
     * Get regsitered 
     * 
     * @param string $name middleware key
     * 
     * @return string
     */
    public function getPath($name)
    {
        $this->validateMiddleware($name);
        return $this->registered[$name];
    }

}