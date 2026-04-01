<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Profit extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick, $code = NULL)
    {
        if (empty($code)) {
            return $rawClick->getProfit();
        }
        return \Core\Currency\Service\CurrencyService::instance()->exchangeToCurrency($rawClick->getProfit(), $code);
    }
}

?>