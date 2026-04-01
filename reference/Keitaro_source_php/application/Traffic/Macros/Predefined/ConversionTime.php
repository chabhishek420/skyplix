<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class ConversionTime extends \Traffic\Macros\AbstractConversionMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\Model\Conversion $conversion, $format = "c")
    {
        return $conversion->getPostbackDatetime()->format($format);
    }
}

?>