<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\HitLimit\Storage;

class Factory
{
    public static function build($redisAvailable)
    {
        if ($redisAvailable) {
            return new RedisStorage();
        }
        return new NullStorage();
    }
}

?>