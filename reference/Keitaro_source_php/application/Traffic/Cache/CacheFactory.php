<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Cache;

class CacheFactory
{
    const COMMON = "common";
    public static function build($name, $storage, $opts = [])
    {
        if (empty($storage)) {
            $storage = Cache::STORAGE_OFF;
        }
        if (!in_array($storage, self::getStorages()) && $storage != Cache::STORAGE_ARRAY) {
            $storage = self::_getAnyStorage();
        }
        if (!CacheFactory::isAllowed($storage)) {
            throw new CacheError("Storage '" . $storage . "' is not available");
        }
        return new Cache($name, $storage, $opts);
    }
    public static function getAvailableStorages()
    {
        $options = [];
        $options[] = ["value" => "auto", "name" => "Auto"];
        foreach (self::getStorages() as $storage) {
            if (self::isAllowed($storage)) {
                $options[] = ["value" => $storage, "name" => ucfirst($storage)];
            }
        }
        return $options;
    }
    public static function getStorages()
    {
        return [Cache::STORAGE_FILES, Cache::STORAGE_REDIS];
    }
    public static function isAllowed($storage)
    {
        switch ($storage) {
            case Cache::STORAGE_APC:
                return extension_loaded("apc") && ini_get("apc.enabled");
                break;
            case Cache::STORAGE_MEMCACHE:
                return function_exists("memcache_connect");
                break;
            case Cache::STORAGE_MEMCACHED:
                return class_exists("Memcached");
                break;
            case Cache::STORAGE_XCACHE:
                return extension_loaded("xcache") && function_exists("xcache_get");
                break;
            case Cache::STORAGE_SQLITE:
                return extension_loaded("pdo_sqlite");
                break;
            case Cache::STORAGE_REDIS:
                return extension_loaded("redis");
                break;
            case Cache::STORAGE_FILES:
            case Cache::STORAGE_ARRAY:
            case Cache::STORAGE_OFF:
                return true;
                break;
            default:
                return false;
        }
    }
    protected static function _getAnyStorage()
    {
        $variations = [Cache::STORAGE_FILES, Cache::STORAGE_OFF];
        foreach ($variations as $tryStorage) {
            if (self::isAllowed($tryStorage)) {
                return $tryStorage;
            }
        }
        return Cache::STORAGE_FILES;
    }
}

?>