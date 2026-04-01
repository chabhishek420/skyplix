<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\Storage;

class NullStorage implements StorageInterface
{
    public function enableCompression()
    {
    }
    public function set($key, $data)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[NullStorage] skip save");
    }
    public function commit()
    {
    }
    public function get($key)
    {
        throw new \Traffic\Cache\NoCache("no key '" . $key . "'");
    }
    public function delete($key)
    {
    }
    public function deleteAll()
    {
    }
    public function exists($key)
    {
        return false;
    }
    public function info()
    {
        return [\Doctrine\Common\Cache\Cache::STATS_MEMORY_USAGE => 0];
    }
    public function size()
    {
    }
}

?>