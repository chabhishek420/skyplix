<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\Storage;

class RedisStorage implements StorageInterface
{
    private $_redisService = NULL;
    private $_delayed = [];
    private $_compressionEnabled = false;
    const CACHE_PREFIX = "PD::";
    public function __construct()
    {
        $this->_redisService = \Traffic\Redis\Service\RedisStorageService::instance();
    }
    public function enableCompression()
    {
        $this->_compressionEnabled = true;
    }
    public function set($key, $data)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[RedisStorage] Save key " . $key . " " . (empty($data) ? "(empty value)" : ""));
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        if ($this->_compressionEnabled) {
            $data = gzcompress($data, 9);
        }
        $this->_delayed[] = [CACHE_PREFIX . $key, $data];
    }
    public function commit()
    {
        $pipe = $this->_redisService->multi(\Redis::PIPELINE);
        $prefix = $this->_redisService->prefix();
        foreach ($this->_delayed as $item) {
            $pipe->set($prefix . $item[0], $item[1]);
        }
        $pipe->exec();
        $this->_delayed = [];
    }
    public function get($key)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[RedisStorage] Read key " . $key);
        $result = $this->_redisService->get(CACHE_PREFIX . $key);
        if ($result === false) {
            throw new \Traffic\Cache\NoCache("no key '" . $key . "'");
        }
        if ($this->_compressionEnabled) {
            $result = gzuncompress($result);
        }
        $result = json_decode($result, JSON_OBJECT_AS_ARRAY);
        if (json_last_error()) {
            \Traffic\Logging\Service\LoggerService::instance()->error("[RedisStorage] Error (" . json_last_error() . ") " . json_last_error_msg());
        }
        return $result;
    }
    public function delete($key)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[RedisStorage] Delete " . $key);
        $this->_redisService->del(CACHE_PREFIX . $key);
    }
    public function deleteAll()
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[RedisStorage] Delete all");
        foreach ($this->_redisService->keys(CACHE_PREFIX . "*") as $key) {
            $this->_redisService->getOriginalClient()->del($key);
        }
    }
    public function exists($key)
    {
        return $this->_redisService->exists(CACHE_PREFIX . $key);
    }
    public function info()
    {
        return $this->_redisService->stats();
    }
    public function size()
    {
        return $this->info()[\Doctrine\Common\Cache\Cache::STATS_MEMORY_USAGE];
    }
}

?>