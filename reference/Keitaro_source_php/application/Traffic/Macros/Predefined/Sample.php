<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Sample extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $visitor, $var1 = NULL, $var2 = NULL, $varN = NULL)
    {
        $params = func_get_args();
        if (count($params) <= 2) {
            return "";
        }
        $key = rand(2, count($params) - 1);
        if (is_string($params[$key])) {
            return trim($params[$key]);
        }
        return "";
    }
}

?>