<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Session\Storage;

class RedisStorage implements StorageInterface
{
    private $_compressionEnabled = false;
    const KEY = "IP_SESSIONS";
    const COMPRESSION_LEVEL = 9;
    public function __construct()
    {
        $this->_compressionEnabled = \Core\Application\Application::instance()->isCompressionEnabled();
    }
    public function save($save, $uniquenessId, \Traffic\Session\SessionEntry $entry, string $ttlInSec)
    {
        $redisKey = $this->_getKey($uniquenessId);
        $encodedData = json_encode($entry->getData());
        if ($this->_compressionEnabled) {
            $encodedData = gzcompress($encodedData, COMPRESSION_LEVEL);
        }
        $oldTTL = \Traffic\Redis\Service\RedisStorageService::instance()->ttl($redisKey);
        $ttlInSec = max($oldTTL, $ttlInSec);
        \Traffic\Redis\Service\RedisStorageService::instance()->setEx($redisKey, $ttlInSec, $encodedData);
    }
    public function getSessionEntry(\Traffic\Session\SessionEntry $getSessionEntry, $uniquenessId)
    {
        $redisKey = $this->_getKey($uniquenessId);
        $data = \Traffic\Redis\Service\RedisStorageService::instance()->get($redisKey);
        if (empty($data)) {
            return \Traffic\Session\SessionEntry::restore([]);
        }
        if ($this->_compressionEnabled) {
            $firstByte = mb_substr($data, 0, 1);
            if ($firstByte !== "\"" && $firstByte !== "{") {
                $data = gzuncompress($data);
            }
        }
        $decodedData = json_decode($data, true);
        return \Traffic\Session\SessionEntry::restore($decodedData);
    }
    private function _getKey($uniquenessId)
    {
        return KEY . ":" . $uniquenessId;
    }
}

?>