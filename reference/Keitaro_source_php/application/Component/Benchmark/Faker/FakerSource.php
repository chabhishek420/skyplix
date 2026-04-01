<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark\Faker;

class FakerSource
{
    public static function get()
    {
        return md5(rand(1000, 9999));
    }
}

?>