<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class DeviceType extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick, $lang = NULL)
    {
        if (empty($lang) || $lang == \Core\Locale\LocaleService::EN) {
            $rawClick->getDeviceType() ? exit : "";
        } else {
            return \Component\Device\Repository\DeviceTypeRepository::instance()->getName($rawClick->getDeviceType());
        }
    }
}

?>