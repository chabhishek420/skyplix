<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Subid extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $raw, $xSeparator = false)
    {
        $subid = $raw->getSubId();
        if ($xSeparator) {
            $subid = str_replace("-", "x", $subid);
        }
        return $subid;
    }
}

?>