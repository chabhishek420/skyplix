<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Offer extends \Traffic\Macros\AbstractClickMacro
{
    public function alwaysRaw()
    {
        return true;
    }
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick, $offerId = NULL)
    {
        $url = \Traffic\Service\UrlService::instance()->getBaseUrl($this->getServerRequest()->getUri(), 1) . "/?_lp=1";
        $url = \Traffic\Service\UrlService::instance()->addParameterToUrl($url, "_token=" . $rawClick->get("token"));
        if ($offerId) {
            $url = \Traffic\Service\UrlService::instance()->addParameterToUrl($url, "&offer_id=" . urlencode($offerId));
        }
        return $url;
    }
}

?>