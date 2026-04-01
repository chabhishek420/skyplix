<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Country extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick, $lang = NULL)
    {
        if (empty($lang)) {
            $rawClick->getCountry() ? exit : "";
        } else {
            if (!in_array($lang, [\Core\Locale\LocaleService::EN, \Core\Locale\LocaleService::RU])) {
                return NULL;
            }
            return \Component\GeoDb\Repository\CountriesRepository::instance()->getCountryName($rawClick->getCountry(), $lang);
        }
    }
}

?>