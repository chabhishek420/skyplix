<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class ConversionProfit extends \Traffic\Macros\AbstractConversionMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\Model\Conversion $conversion, $code = NULL)
    {
        if (empty($code)) {
            return $conversion->getProfit();
        }
        return \Core\Currency\Service\CurrencyService::instance()->exchangeToCurrency($conversion->getProfit(), $code);
    }
}

?>