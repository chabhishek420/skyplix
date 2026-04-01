<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Cache;

class Cache
{
    private $_name = NULL;
    private $_storageName = NULL;
    private $_doctrineCache = NULL;
    private $_options = NULL;
    const STORAGE_OFF = "off";
    const STORAGE_APC = "apc";
    const STORAGE_AUTO = "auto";
    const STORAGE_SQLITE = "sqlite";
    const STORAGE_FILES = "files";
    const STORAGE_REDIS = "redis";
    const STORAGE_MEMCACHE = "memcache";
    const STORAGE_XCACHE = "xcache";
    const STORAGE_MEMCACHED = "memcached";
    const STORAGE_ARRAY = "array";
    const CACHE_DIRECTORY = "cache_directory";
    const DEFAULT_CACHE_DIR = "/var/cache";
    const VALIDATION_TIMESTAMP = "TIMESTAMP";
    public function __construct($name, $storage, $opts = [])
    {
        $this->_name = $name;
        $this->_storageName = $storage;
        $this->_options = $opts;
        $this->_rebuildDoctrineCache();
    }
    public function getStorageName()
    {
        return $this->_storageName;
    }
    public function getDoctrineCache()
    {
        return $this->_doctrineCache;
    }
    public function setOption($name, $value)
    {
        $this->_options[$name] = $value;
        $this->_rebuildDoctrineCache();
        return $this;
    }
    public function getOption($name)
    {
        return isset($this->_options[$name]) ? $this->_options[$name] : NULL;
    }
    private function _getNamespace()
    {
        return strtoupper($this->_name) . "_" . $this->_getNamespaceHash();
    }
    private function _getNamespaceHash()
    {
        return strtoupper(substr(md5(SALT), 10, 5));
    }
    public function set($key, $value, $ttl = 0)
    {
        return $this->_doctrineCache->save($key, $value, $ttl);
    }
    public function get($key)
    {
        $result = $this->_doctrineCache->fetch($key);
        if ($result === false) {
            throw new NoCache("no key '" . $key . "'");
        }
        return $result;
    }
    public function delete($key)
    {
        return $this->_doctrineCache->delete($key);
    }
    public function isExists($key)
    {
        return $this->_doctrineCache->contains($key);
    }
    public function deleteAll()
    {
        return $this->_doctrineCache->deleteAll();
    }
    public function flushAll()
    {
        return $this->_doctrineCache->flushAll();
    }
    public function getStats()
    {
        return $this->_doctrineCache->getStats();
    }
    public function getSize()
    {
        $stats = $this->getStats();
        if (!empty($stats["memory_usage"])) {
            return $stats["memory_usage"];
        }
        return 0;
    }
    public function validateCache()
    {
        $time = time();
        $this->set(VALIDATION_TIMESTAMP, $time, CacheService::MAX_TTL);
        $checkTime = $this->get(VALIDATION_TIMESTAMP);
        if ($checkTime < $time) {
            throw new CacheInvalid("Cache storage is read-only! " . $checkTime . "-" . $time);
        }
    }
    public function flushOldNamespacesCache()
    {
        $cleaner = FlushOldNamespacedCacheService::instance();
        $cleaner->init($this->getDoctrineCache(), $this->_getNamespaceHash());
        return $cleaner->flush();
    }
    private function _rebuildDoctrineCache()
    {
        switch ($this->_storageName) {
            case STORAGE_OFF:
                $cacheDriver = new \Doctrine\Common\Cache\VoidCache();
                break;
            case STORAGE_ARRAY:
                $cacheDriver = new \Doctrine\Common\Cache\ArrayCache();
                break;
            case STORAGE_REDIS:
                $redis = \Traffic\Redis\Service\RedisStorageService::instance()->newRedisInstance();
                $cacheDriver = new \Doctrine\Common\Cache\RedisCache();
                $cacheDriver->setRedis($redis);
                break;
            case STORAGE_APC:
            case STORAGE_FILES:
            case STORAGE_MEMCACHE:
            case STORAGE_MEMCACHED:
            case STORAGE_XCACHE:
                $path = $this->getOption(CACHE_DIRECTORY);
                if (empty($path)) {
                    throw new CacheError("Option 'cache_directory' is empty");
                }
                $cacheDriver = new \Doctrine\Common\Cache\FilesystemCache($path);
                $cacheDriver->setNamespace($this->_getNamespace());
                $this->_doctrineCache = $cacheDriver;
                return $this->_doctrineCache;
                break;
            default:
                throw new CacheError("Storage '" . $this->_storageName . "' is not available");
        }
    }
}

?>