<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\GeoDb;

class IpInfoType
{
    const COUNTRY = "country";
    const REGION = "region";
    const CITY = "city";
    const CITY_RU = "city_ru";
    const ISP = "isp";
    const PROXY_TYPE = "proxy_type";
    const BOT_TYPE = "bot_type";
    const CONNECTION_TYPE = "connection_type";
    const OPERATOR = "operator";
    public static function all()
    {
        return [COUNTRY, REGION, CITY, CITY_RU, ISP, PROXY_TYPE, BOT_TYPE, CONNECTION_TYPE, OPERATOR];
    }
}

?>