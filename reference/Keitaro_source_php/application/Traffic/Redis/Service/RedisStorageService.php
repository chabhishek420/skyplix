<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Redis\Service;

class RedisStorageService extends \Traffic\Service\AbstractService
{
    private $_forceDisabled = false;
    private $_redis = NULL;
    private $_initialized = false;
    const DRAFT_DATA_STORAGE = "draft_data_storage";
    const REDIS = "redis";
    public function init()
    {
        if ($this->_initialized) {
            return NULL;
        }
        $this->_redis = $this->newRedisInstance();
        $this->_initialized = true;
    }
    public function newRedisInstance()
    {
        $uri = $this->_getRedisUri();
        if (!class_exists("Redis")) {
            throw new \Core\Application\Exception\Error("Extension php_redis not installed!");
        }
        $redis = new \Redis();
        $url = parse_url($uri);
        if (!isset($url["host"]) || !isset($url["port"])) {
            throw new \Core\Application\Exception\Error("Redis URL incorrect");
        }
        try {
            $redis->pconnect($url["host"], $url["port"]);
            if (!empty($url["user"])) {
                $inPass = $url["user"];
                $passToTry = [$inPass, hash("sha256", $inPass), hash("sha256", $inPass . "\n")];
                foreach ($passToTry as $pass) {
                    $res = $redis->auth($pass);
                    if ($res) {
                    }
                }
            }
            if (!empty($url["path"])) {
                $redis->select(str_replace("/", "", $url["path"]));
            }
        } catch (\RedisException $e) {
            $this->_forceDisabled = true;
            \Traffic\Logging\Service\LoggerService::instance()->error("Error while connecting redis: " . $e->getMessage());
            return $redis;
        }
    }
    private function _getRedisUri()
    {
        $uri = \Traffic\Service\ConfigService::instance()->get("redis", "uri");
        if (empty($uri)) {
            $uri = \Traffic\Repository\CachedSettingsRepository::instance()->get("redis_server");
        }
        return $uri;
    }
    public function getOriginalClient()
    {
        $this->init();
        return $this->_redis;
    }
    public function clean($force = false)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[RediStorageService] clean");
        if (!$this->isEnabled() && !$force) {
            return NULL;
        }
        $this->init();
        return $this->getOriginalClient()->flushDB();
    }
    public function prefix()
    {
        $prefix = strtoupper(substr(md5(SALT), 10, 5)) . "::";
        $prefix .= strtoupper(\Core\Application\Application::instance()->getEnv()) . "::";
        return $prefix;
    }
    public function disable()
    {
        $this->_forceDisabled = true;
    }
    public function enable()
    {
        $this->_forceDisabled = false;
    }
    public function redisServerExists()
    {
        return class_exists("Redis");
    }
    public function draftStorageUsesRedis()
    {
        return $this->isEnabled() && \Traffic\Repository\CachedSettingsRepository::instance()->get(DRAFT_DATA_STORAGE) == REDIS;
    }
    public function isEnabled()
    {
        if ($this->_forceDisabled) {
            return false;
        }
        if (!$this->redisServerExists()) {
            return false;
        }
        if (!class_exists("Redis")) {
            return false;
        }
        return true;
    }
    public function isRedisAlive()
    {
        try {
            $pingReply = $this->getOriginalClient()->ping();
            if ($pingReply !== "+PONG" && $pingReply != "1") {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    public function __call($method, $args)
    {
        $this->init();
        if (isset($args[0]) && is_string($args[0])) {
            $args[0] = $this->prefix() . $args[0];
        }
        if (!method_exists($this->getOriginalClient(), $method)) {
            throw new \Core\Application\Exception\Error("No method Redis#" . $method);
        }
        try {
            return call_user_func_array([$this->getOriginalClient(), $method], $args);
        } catch (\Core\Application\Exception\Error $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error("Error while send command to redis: " . $e->getMessage() . ". Saving data to DB.");
            $this->_forceDisabled = true;
        }
    }
    public function scan($Iterator, $pattern = NULL, $count = NULL)
    {
        return $this->__call("scan", [$Iterator, $pattern, $count]);
    }
    public function stats()
    {
        $info = $this->getOriginalClient()->info();
        return [\Doctrine\Common\Cache\Cache::STATS_HITS => $info["keyspace_hits"], \Doctrine\Common\Cache\Cache::STATS_MISSES => $info["keyspace_misses"], \Doctrine\Common\Cache\Cache::STATS_UPTIME => $info["uptime_in_seconds"], \Doctrine\Common\Cache\Cache::STATS_MEMORY_USAGE => $info["used_memory"], \Doctrine\Common\Cache\Cache::STATS_MEMORY_AVAILABLE => false];
    }
}

?>