<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class XRequestedWith extends \Traffic\Macros\AbstractClickMacro
{
    public function process($process, \Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick = NULL, $code)
    {
        $rawClick->getXRequestedWith() ? exit : "";
    }
}

?>