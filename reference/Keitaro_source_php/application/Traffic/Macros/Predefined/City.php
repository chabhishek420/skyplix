<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class City extends \Traffic\Macros\AbstractClickMacro
{
    const RU = "ru";
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick, $lang = NULL)
    {
        if (empty($lang)) {
            $lang = \Core\Locale\LocaleService::instance()->getLanguage();
        }
        if ($lang == RU) {
            $db = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->getDbForDataType(\Traffic\GeoDb\IpInfoType::CITY_RU);
            if (empty($db)) {
                $rawClick->getCity() ? exit : "";
            } else {
                $info = \Traffic\GeoDb\Service\GeoDbService::instance()->info($db, $rawClick->getIpString());
                return isset($info[\Traffic\GeoDb\IpInfoType::CITY_RU]) ? $info[\Traffic\GeoDb\IpInfoType::CITY_RU] : NULL;
            }
        } else {
            $rawClick->getCity() ? exit : "";
        }
    }
}

?>