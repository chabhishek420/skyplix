<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class ConversionRevenue extends \Traffic\Macros\AbstractConversionMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\Model\Conversion $conversion, $code = NULL)
    {
        if (empty($code)) {
            return (int) $conversion->getRevenue();
        }
        return (int) \Core\Currency\Service\CurrencyService::instance()->exchangeToCurrency($conversion->getRevenue(), $code);
    }
}

?>