<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\ConversionCapacity\Storage;

class RedisStorage implements \Component\Conversions\ConversionCapacity\StorageInterface
{
    const ZNAME = "daily_cap:";
    const TTL = 2;
    public function store(\Traffic\Model\Offer $offer, \Traffic\Model\Conversion $conversion)
    {
        $zName = $this->_getName($offer->getId());
        \Traffic\Redis\Service\RedisStorageService::instance()->zadd($zName, $conversion->getPostbackDatetime()->getTimestamp(), $this->_getRand());
    }
    public function currentValueForOffer(\Traffic\Model\Offer $offer, \DateTime $currentDateTime)
    {
        $tzName = $offer->get("conversion_timezone") ? $offer->get("conversion_timezone") : "UTC";
        $currentDateTime = clone $currentDateTime;
        $timezone = new \DateTimeZone($tzName);
        $currentDateTime->setTimezone($timezone);
        $from = $currentDateTime->setTime(0, 0, 0)->getTimestamp();
        return \Traffic\Redis\Service\RedisStorageService::instance()->zcount($this->_getName($offer->getId()), $from, "+inf");
    }
    public function totalValueForOffer(\Traffic\Model\Offer $offer)
    {
        return \Traffic\Redis\Service\RedisStorageService::instance()->zcount($this->_getName($offer->getId()), "-inf", "+inf");
    }
    public function prune(\DateTime $currentDateTime)
    {
        $zNames = \Traffic\Redis\Service\RedisStorageService::instance()->keys(ZNAME . "*");
        $time = clone $currentDateTime;
        $until = $time->modify("-" . TTL . " days")->getTimestamp();
        if (!empty($zNames)) {
            foreach ($zNames as $name) {
                \Traffic\Redis\Service\RedisStorageService::instance()->getOriginalClient()->zRemRangeByScore($name, "-inf", $until);
            }
        }
    }
    private function _getName($offerId)
    {
        return ZNAME . $offerId;
    }
    private function _getRand()
    {
        return date("YmdHis") . rand(10000, 999999);
    }
}

?>