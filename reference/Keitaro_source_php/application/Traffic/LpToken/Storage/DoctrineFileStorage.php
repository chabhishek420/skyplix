<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\LpToken\Storage;

class DoctrineFileStorage implements StorageInterface
{
    public function enableCompression()
    {
    }
    public function set($token, $value, $ttl)
    {
        \Traffic\Cache\CacheService::instance()->lpOfferCache()->set($token, $value, $ttl);
    }
    public function get($token)
    {
        try {
            return \Traffic\Cache\CacheService::instance()->lpOfferCache()->get($token);
        } catch (\Traffic\Cache\NoCache $e) {
        }
    }
    public function delete($token)
    {
        \Traffic\Cache\CacheService::instance()->lpOfferCache()->delete($token);
    }
}

?>