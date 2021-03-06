<?php

namespace Obullo\Cache;

/**
 * Cache Handler Interface
 * 
 * @copyright 2009-2016 Obullo
 * @license   http://opensource.org/licenses/MIT MIT license
 */
interface CacheInterface
{
    /**
     * Verify if the specified key exists.
     * 
     * @param string $key cache key.
     * 
     * @return boolean true or false
     */
    public function hasItem($key);

    /**
     * Set cache data.
     *
     * @param mix $key  cache key
     * @param mix $data cache data
     * @param int $ttl  expiration time
     * 
     * @return boolean
     */
    public function setItem($key, $data, $ttl = 60);

    /**
     * Set keys
     * 
     * @param array   $data key - value
     * @param integer $ttl  ttl
     *
     * @return boolean
     */
    public function setItems(array $data, $ttl = 60);

    /**
     * Get cache data.
     *
     * @param string $key cache key
     * 
     * @return mix
     */
    public function getItem($key);

    /**
     * Get multiple items
     * 
     * @param string $key cache key
     * 
     * @return mix
     */
    public function getItems(array $key);

    /**
     * Replace cache data
     * 
     * @param mix $key  cache key
     * @param mix $data cache data
     * @param int $ttl  expiration time
     * 
     * @return boolean
     */
    public function replaceItem($key, $data, $ttl = 60);

    /**
     * Replace data
     * 
     * @param array   $data key - value
     * @param integer $ttl  ttl
     * 
     * @return boolean
     */
    public function replaceItems(array $data, $ttl = 60);

    /**
     * Remove specified keys.
     * 
     * @param string $key cache key.
     * 
     * @return boolean
     */
    public function removeItem($key);

    /**
     * Remove specified keys.
     * 
     * @param array $keys keys
     * 
     * @return void
     */
    public function removeItems(array $keys);

    /**
     * Flushes all data from cache.
     * 
     * @return bool
     */
    public function flushAll();

}