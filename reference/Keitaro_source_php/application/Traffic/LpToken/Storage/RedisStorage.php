<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\LpToken\Storage;

class RedisStorage implements StorageInterface
{
    private $_compressionEnabled = false;
    private $_prefix = NULL;
    private $_redis = NULL;
    const NAME = "RW_CLCS";
    const COMPRESSION_LEVEL = 9;
    public function __construct($compression = false)
    {
        $this->_prefix = strtoupper(substr(md5(SALT), 10, 5));
        $this->_compressionEnabled = $compression;
        $this->_redis = \Traffic\Redis\Service\RedisStorageService::instance();
    }
    public function enableCompression()
    {
        $this->_compressionEnabled = true;
    }
    public function set($token, $value, $ttl)
    {
        $key = $this->_buildKey($token);
        if ($this->_compressionEnabled) {
            $value = gzcompress($value, COMPRESSION_LEVEL);
        }
        $this->_redis->setEx($key, (int) $ttl, $value);
    }
    public function get($token)
    {
        $key = $this->_buildKey($token);
        $result = $this->_redis->get($key);
        if ($result !== false && $this->_compressionEnabled) {
            $result = gzuncompress($result);
        }
        return $result;
    }
    public function delete($token)
    {
        $this->_redis->delete($this->_buildKey($token));
    }
    private function _buildKey($token)
    {
        return NAME . ":" . $token;
    }
}

?>