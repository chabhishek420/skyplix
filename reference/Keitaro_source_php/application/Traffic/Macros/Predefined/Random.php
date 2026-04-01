<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Random extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick)
    {
        func_num_args();
        switch (func_num_args()) {
            case 2:
                $min = 0;
                $max = 9999;
                break;
            case 3:
                $min = 1;
                $max = func_get_arg(2);
                break;
            case 4:
                $min = func_get_arg(2);
                $max = func_get_arg(3);
                break;
            default:
                return rand((int) $min, (int) $max);
        }
    }
}

?>