<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Adapter;

final class GeoDbAdapterInterface
{
    public abstract function info($ip);
    public abstract function rawInfo($ip);
}

?>