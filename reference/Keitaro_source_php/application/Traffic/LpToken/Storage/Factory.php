<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\LpToken\Storage;

class Factory
{
    public static function build($compression)
    {
        if (\Traffic\Redis\Service\RedisStorageService::instance()->draftStorageUsesRedis()) {
            $storage = new RedisStorage();
            if ($compression) {
                $storage->enableCompression();
            }
            return $storage;
        }
        return new DoctrineFileStorage();
    }
}

?>