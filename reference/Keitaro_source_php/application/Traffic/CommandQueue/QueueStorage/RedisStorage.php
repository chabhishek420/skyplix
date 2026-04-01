<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CommandQueue\QueueStorage;

class RedisStorage implements StorageInterface
{
    private $_queueName = NULL;
    private $_compressionEnabled = NULL;
    private $_redisService = NULL;
    private $_rangeSize = NULL;
    const QUEUE = "COMMAND_QUEUE";
    const ADDITIONAL_REDIS_QUEUE = "additional_redis_queue";
    const COMPRESSION_LEVEL = 9;
    const RANGE_SIZE = 1000;
    public function __construct($compression = false)
    {
        $this->setQueueName(QUEUE);
        $this->_compressionEnabled = $compression;
        $this->_redisService = \Traffic\Redis\Service\RedisStorageService::instance();
    }
    public function enableCompression()
    {
        $this->_compressionEnabled = true;
    }
    public function isAvailable()
    {
        return $this->_redisService->isEnabled();
    }
    public function setQueueName($name)
    {
        $this->_queueName = $name;
    }
    public function push($data)
    {
        if ($this->_compressionEnabled) {
            $data = gzcompress($data, COMPRESSION_LEVEL);
        }
        $this->_redisService->rPush($this->getQueueName(), $data);
        if ($queueName = \Traffic\Service\ConfigService::instance()->get("system", ADDITIONAL_REDIS_QUEUE)) {
            $this->_redisService->getOriginalClient()->rPush($queueName, $data);
        }
        return $this;
    }
    public function count()
    {
        return $this->_redisService->lLen($this->getQueueName());
    }
    public function clean()
    {
        $this->_redisService->del($this->getQueueName());
    }
    public function getQueueName()
    {
        return $this->_queueName;
    }
    public function pop()
    {
        $size = RANGE_SIZE;
        $key = $this->_redisService->prefix() . $this->getQueueName();
        while (true) {
            $pipe = $this->_redisService->multi(\Redis::PIPELINE);
            $pipe->lRange($key, 0, $size - 1);
            $pipe->lTrim($key, $size, -1);
            list($result) = $pipe->exec();
            if (!(empty($result) || $result === false)) {
                foreach ($result as $item) {
                    $this->_decode($item);
                }
            }
        }
    }
    private function _decode($data)
    {
        if (empty($data)) {
            return NULL;
        }
        if ($this->_compressionEnabled) {
            $firstByte = mb_substr($data, 0, 1);
            if ($firstByte !== "\"" && $firstByte !== "{") {
                $data = gzuncompress($data);
            }
        }
        return $data;
    }
}

?>