<?php

namespace Obullo\Cache\Handler;

use RuntimeException;
use Obullo\Cache\CacheInterface;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * File Caching Class
 * 
 * @license http://opensource.org/licenses/MIT MIT license
 * @link    http://obullo.com/package/cache
 */
class File implements CacheInterface
{
    const SERIALIZER_NONE = 'none';

    /**
     * Uploaded file path
     * 
     * @var string
     */
    public $filePath = '/tmp/';

    /**
     * Constructor
     *
     * @param array $options options
     */
    public function __construct($options = array())
    {
        if (! empty($options['path'])) {
            $path = $options['path'];
        } else {
            $path = '/resources/data/cache/';
        }
        $this->filePath = ltrim($path, '/');

        if (strpos($this->filePath, 'resources') === 0) {
            $this->filePath = ROOT. $this->filePath . '/';
        }
        $this->connect();
    }

    /**
     * Connect to file.
     * 
     * @return void
     */
    public function connect()
    {
        if (! is_writable($this->filePath)) {
            throw new RuntimeException(
                sprintf(
                    'Filepath %s is not writable.',
                    $this->filePath
                )
            );
        }
    }

    /**
     * Get current serializer name
     * 
     * @return string serializer name
     */
    public function getSerializer()
    {
        return null;
    }

    /**
     * Sets serializer
     * 
     * @param string $serializer type
     *
     * @return void
     */
    public function setSerializer($serializer = 'php')
    {
        return $serializer = null;
    }

    /**
     * Get cache data.
     * 
     * @param string $key cache key
     * 
     * @return object
     */
    public function getItem($key)
    {
        if (! file_exists($this->filePath . $key)) {
            return false;
        }
        $data = file_get_contents($this->filePath . $key);
        $data = unserialize($data);

        if (time() > $data['time'] + $data['ttl']) {
            unlink($this->filePath . $key);
            return false;
        }
        return $data['data'];
    }

    /**
     * Get multiple items.
     * 
     * @param array $keys cache keys
     * 
     * @return array
     */
    public function getItems(array $keys)
    {
        $items = array();
        foreach ($keys as $key) {
            $items[] = $this->getItem($key);
        }
        return $items;
    }

    /**
     * Verify if the specified key exists.
     * 
     * @param string $key storage key
     * 
     * @return boolean true or false
     */
    public function hasItem($key)
    {
        if ($this->getItem($key) == false) {
            return false;
        }
        return true;
    }

    /**
     * Replace cache data.
     * 
     * @param string  $key  key
     * @param string  $data string data
     * @param integer $ttl  expiration
     * 
     * @return boolean
     */
    public function replaceItem($key, $data, $ttl = 60)
    {
        $this->removeItem($key);

        $contents = array(
            'time' => time(),
            'ttl'  => $ttl,
            'data' => $data
        );
        $fileName = $this->filePath . $key;
        if ($this->writeData($fileName, $contents)) {
            return true;
        }
        return false;
    }
    
    /**
     * Replace data
     * 
     * @param array   $data key - value
     * @param integer $ttl  ttl
     * 
     * @return boolean
     */
    public function replaceItems(array $data, $ttl = 60)
    {
        return $this->setArray($data, $ttl);
    }

    /**
     * Set item
     * 
     * @param string $key  cache key.
     * @param array  $data cache data.
     * @param int    $ttl  expiration time.
     * 
     * @return boolean
     */
    public function setItem($key, $data, $ttl = 60)
    {
        $contents = array(
            'time' => time(),
            'ttl'  => $ttl,
            'data' => $data
        );
        $fileName = $this->filePath . $key;
        if ($this->writeData($fileName, $contents)) {
            return true;
        }
        return false;
    }

    /**
     * Set items
     * 
     * @param array   $data data
     * @param integer $ttl  ttl
     *
     * @return boolean
     */
    public function setItems(array $data, $ttl = 60)
    {
        return $this->setArray($data, $ttl);
    }

    /**
     * Remove item
     * 
     * @param string $key cache key.
     * 
     * @return boolean
     */
    public function removeItem($key)
    {
        if (file_exists($this->filePath . $key)) {
            return unlink($this->filePath . $key);
        }
        return false;
    }

    /**
     * Remove specified keys.
     * 
     * @param array $keys keys
     * 
     * @return void
     */
    public function removeItems(array $keys)
    {
        foreach ($keys as $key) {
            $this->removeItem($key);
        }
        return;
    }

    /**
     * Get all keys
     * 
     * @return array
     */
    public function getAllKeys()
    {
        $dh = opendir($this->filePath);
        while (false !== ($fileName = readdir($dh))) {
            if (substr($fileName, 0, 1) !== '.') {
                $files[] = $fileName;
            }
        }
        return $files;
    }

    /**
     * Get all data
     * 
     * @return array
     */
    public function getAllData()
    {
        $dh = opendir($this->filePath);

        while (false !== ($fileName = readdir($dh))) {
            if (substr($fileName, 0, 1) !== '.') {
                $temp = file_get_contents($this->filePath . $fileName);
                $temp = unserialize($temp);
                if (time() > $temp['time'] + $temp['ttl']) {
                    unlink($this->filePath . $fileName);
                    return false;
                }
                $data[$fileName] = $temp['data'];
            }
        }
        return (empty($data)) ? null : $data;
    }

    /**
     * Clean all data
     * 
     * @return boolean
     */
    public function flushAll()
    {
        $dh  = opendir($this->filePath);
        while (false !== ($fileName = readdir($dh))) {
            if (substr($fileName, 0, 1) !== '.') {
                unlink($this->filePath . $fileName);
            }
        }
    }

    /**
     * Returns to splFileInfo objects
     * 
     * @return array
     */
    public function getInfo()
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->filePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        $info = array();
        foreach ($iterator as $splFileInfo) {
            $info[] = $splFileInfo;
        }
        return $info;
    }

    /**
     * Set Array
     * 
     * @param array $data data
     * @param int   $ttl  expiration
     *
     * @return void
     */
    protected function setArray(array $data, $ttl)
    {
        foreach ($data as $k => $v) {
            $contents = array(
                'time' => time(),
                'ttl'  => $ttl,
                'data' => $v
            );
            $fileName = $this->filePath . $k;
            $write    = $this->writeData($fileName, $contents);
        }
        if (! $write) {
            return false;
        }
        return true;
    }

    /**
     * Write data
     *
     * @param string $fileName file name
     * @param array  $contents contents
     * 
     * @return boolean true or false
     */
    protected function writeData($fileName, $contents)
    {
        if (! $fp = fopen($fileName, 'wb')) {
            return false;
        }
        $serializeData = serialize($contents);
        flock($fp, LOCK_EX);
        fwrite($fp, $serializeData);
        flock($fp, LOCK_UN);
        fclose($fp);
        return true;
    }

    /**
     * Close the connection
     * 
     * @return void
     */
    public function close()
    {
        return;
    }
}