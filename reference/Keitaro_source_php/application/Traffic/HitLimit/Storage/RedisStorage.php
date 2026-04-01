<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\HitLimit\Storage;

class RedisStorage implements StorageInterface
{
    const SET_PREFIX = "rate:";
    const FILTER_NAME = "limit";
    const COLLECTION_SET = "rate_collection";
    const TTL = 1;
    public function store(\Traffic\Model\BaseStream $stream, \DateTime $currentDateTime)
    {
        \Traffic\Redis\Service\RedisStorageService::instance()->zadd($this->_getSetName($stream->getId()), $currentDateTime->getTimestamp(), $this->_getRand());
        \Traffic\Redis\Service\RedisStorageService::instance()->sAdd(COLLECTION_SET, $stream->getId());
    }
    public function perHour(\Traffic\Model\BaseStream $stream, \DateTime $currentDateTime)
    {
        $currentDateTime = clone $currentDateTime;
        $from = $currentDateTime->modify("-1 hour")->getTimestamp();
        return \Traffic\Redis\Service\RedisStorageService::instance()->zcount($this->_getSetName($stream->getId()), $from, "+inf");
    }
    public function perDay(\Traffic\Model\BaseStream $stream, \DateTime $date)
    {
        $date = clone $date;
        $from = $date->modify("-1 day")->getTimestamp();
        return \Traffic\Redis\Service\RedisStorageService::instance()->zcount($this->_getSetName($stream->getId()), $from, "+inf");
    }
    public function total(\Traffic\Model\BaseStream $stream)
    {
        return \Traffic\Redis\Service\RedisStorageService::instance()->zcount($this->_getSetName($stream->getId()), "-inf", "+inf");
    }
    private function _getSetName($streamId)
    {
        return SET_PREFIX . $streamId;
    }
    private function _getRand()
    {
        return date("YmdHis") . rand(10000, 999999);
    }
    public function prune(\DateTime $currentDateTime)
    {
        $exceptions = $this->_getStreamIdsWithLimitTotal();
        $zNames = \Traffic\Redis\Service\RedisStorageService::instance()->keys(SET_PREFIX . "*");
        $time = clone $currentDateTime;
        $until = $time->modify("-" . TTL . " day")->getTimestamp();
        if (is_array($zNames)) {
            foreach ($zNames as $name) {
                $streamId = str_replace(SET_PREFIX, "", $name);
                $streamId = str_replace(\Traffic\Redis\Service\RedisStorageService::instance()->prefix(), "", $streamId);
                if (!in_array($streamId, $exceptions)) {
                    \Traffic\Redis\Service\RedisStorageService::instance()->getOriginalClient()->zRemRangeByScore($name, "-inf", $until);
                }
            }
        }
    }
    private function _getStreamIdsWithLimitTotal()
    {
        $where = "name = " . \Core\Db\Db::quote(FILTER_NAME);
        $ids = [];
        foreach (\Component\StreamFilters\Repository\StreamFilterRepository::instance()->all($where) as $filter) {
            $payload = $filter->getPayload();
            if (!empty($payload["total"])) {
                $ids[] = $filter->getStreamId();
            }
        }
        return $ids;
    }
}

?>