<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\Storage;

class Factory
{
    public static function build($cacheName, $compressionEnabled)
    {
        switch ($cacheName) {
            case \Traffic\Cache\Cache::STORAGE_REDIS:
                $storage = new RedisStorage();
                if ($compressionEnabled) {
                    $storage->enableCompression();
                }
                return $storage;
                break;
            case \Traffic\Cache\Cache::STORAGE_OFF:
                return new NullStorage();
                break;
            default:
                return new FileStorage();
        }
    }
}

?>