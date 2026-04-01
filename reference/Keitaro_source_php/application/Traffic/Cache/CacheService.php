<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Cache;

class CacheService extends \Traffic\Service\AbstractService
{
    private $_caches = [];
    const COMMON = "common";
    const LP_OFFER = "landing_offer";
    const CACHED_DATA = "PD";
    const DEVICE_DETECTOR = "device_detector";
    const SETTINGS = "settings";
    public function setCache($name, $cache)
    {
        $this->_caches[$name] = $cache;
    }
    public function setCaches($caches)
    {
        return $this->_caches = array_merge($this->_caches, $caches);
    }
    public function commonCache()
    {
        return $this->_caches[COMMON];
    }
    public function settingsCache()
    {
        return $this->_caches[SETTINGS];
    }
    public function lpOfferCache()
    {
        return $this->_caches[LP_OFFER];
    }
    public function dataCache()
    {
        return $this->_caches[CACHED_DATA];
    }
    public function deviceDetectorCache()
    {
        return $this->_caches[DEVICE_DETECTOR];
    }
    public function totalSize()
    {
        return array_reduce($this->_caches, function ($sum, Cache $cache) {
            return $sum + $cache->getSize();
        }, 0);
    }
    public function deleteAllCaches()
    {
        foreach ($this->_caches as $cache) {
            $cache->deleteAll();
        }
    }
    public function flushOldNamespacedCache()
    {
        foreach ($this->_caches as $cache) {
            $cache->flushOldNamespacesCache();
        }
    }
}

?>