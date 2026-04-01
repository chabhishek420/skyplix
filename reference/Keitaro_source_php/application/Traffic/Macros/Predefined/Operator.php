<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Operator extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick, $lang = NULL)
    {
        if (empty($lang)) {
            $rawClick->getOperator() ? exit : "";
        } else {
            return \Component\GeoDb\Repository\OperatorsRepository::instance()->getName($rawClick->getOperator(), $lang);
        }
    }
}

?>