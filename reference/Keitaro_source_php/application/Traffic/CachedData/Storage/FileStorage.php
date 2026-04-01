<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\Storage;

class FileStorage implements StorageInterface
{
    private $_cache = NULL;
    public function __construct()
    {
        $this->_cache = \Traffic\Cache\CacheService::instance()->dataCache();
    }
    public function set($key, $data)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[FileStorage] Save key " . $key . " " . (empty($data) ? "(empty value)" : ""));
        $this->_cache->set($key, $data);
    }
    public function commit()
    {
    }
    public function get($key)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[FileStorage] Read key " . $key);
        return $this->_cache->get($key);
    }
    public function delete($key)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[FileStorage] Delete " . $key);
        $this->_cache->delete($key);
    }
    public function deleteAll()
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[FileStorage] Delete all");
        $this->_cache->deleteAll();
    }
    public function exists($key)
    {
        return $this->_cache->isExists($key);
    }
    public function info()
    {
        return $this->_cache->getStats();
    }
    public function size()
    {
        return $this->_cache->getSize();
    }
}

?>