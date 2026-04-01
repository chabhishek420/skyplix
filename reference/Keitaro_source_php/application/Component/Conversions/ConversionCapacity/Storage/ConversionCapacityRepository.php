<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\ConversionCapacity\Repository;

class ConversionCapacityRepository extends \Traffic\Service\AbstractService
{
    public function getStorages()
    {
        $storages = [];
        $storages[] = new \Component\Conversions\ConversionCapacity\Storage\FileStorage();
        if (\Traffic\Redis\Service\RedisStorageService::instance()->draftStorageUsesRedis()) {
            $storages[] = new \Component\Conversions\ConversionCapacity\Storage\RedisStorage();
        }
        return $storages;
    }
    public function getCurrentStorage()
    {
        if (\Traffic\Redis\Service\RedisStorageService::instance()->draftStorageUsesRedis()) {
            return new \Component\Conversions\ConversionCapacity\Storage\RedisStorage();
        }
        return new \Component\Conversions\ConversionCapacity\Storage\FileStorage();
    }
    public function currentValueForOffer($offer, \DateTime $currentDateTime = NULL)
    {
        if (empty($currentDateTime)) {
            $currentDateTime = new \DateTime();
        }
        return $this->getCurrentStorage()->currentValueForOffer($offer, $currentDateTime);
    }
    public function isCapReached(\Traffic\Model\Offer $offer, \DateTime $currentDateTime = NULL)
    {
        if (!$offer->isConversionCapEnabled()) {
            return false;
        }
        $currentValue = $this->currentValueForOffer($offer, $currentDateTime);
        return $offer->getDailyCap() <= $currentValue;
    }
}

?>