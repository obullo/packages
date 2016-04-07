<?php

namespace Obullo\Container\ServiceProvider;

use League\Container\ServiceProvider\AbstractServiceProvider as LeagueAbstractServiceProvider;

/**
 * AbstractServiceProvider
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
abstract class AbstractServiceProvider extends LeagueAbstractServiceProvider implements ServiceProviderInterface
{
    /**
     * Connection ids
     * 
     * @var array
     */
    protected $connections = array();

    /**
     * Load service parameters
     * 
     * @param string $name name
     * 
     * @return object|null
     */
    public function getConfiguration($name = null)
    {
        if ($name == null) {
            $class = get_class($this);
            $namespace = explode('\\', $class);
            $name = end($namespace);
            $name = strtolower($name);
        }
        $container  = $this->getContainer();
        $parameters = $container->get('config')->get('providers::'.$name);

        if (is_array($parameters)) {

            $config = new Configuration($parameters);
            $container->add($name.'.params', $config->getParams());  // Inject service parameters to container

            return $config;
        }
    }

    /**
     * Creates connection id using class name & key values
     * 
     * @param string $key string
     * 
     * @return string
     */
    public function getConnectionKey($key)
    {
        return get_class($this).'_'.$key;
    }

    /**
     * Creates "Unique" connection id using serialized parameters
     * 
     * @param string $string serialized parameters
     * 
     * @return integer
     */
    public function getConnectionId($string)
    {
        $prefix = get_class($this);
        $connid = $prefix.'_'.sprintf("%u", crc32(serialize($string)));
        $this->connections[$prefix][] = $connid;
        return $connid;
    }
    /**
     * Returns all connections
     * 
     * @return array
     */
    public function getConnections()
    {
        $prefix = get_class($this);
        return $this->connections[$prefix];
    }

}
