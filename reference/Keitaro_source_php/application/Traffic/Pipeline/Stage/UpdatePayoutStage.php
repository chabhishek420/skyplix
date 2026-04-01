<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class UpdatePayoutStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $offer = $payload->getOffer();
        $rawClick = $payload->getRawClick();
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        if (!empty($offer) && $offer->isCPC() && !$offer->isPayoutAuto()) {
            $rawClick->set("is_sale", 1);
            $revenue = \Core\Currency\Service\CurrencyService::instance()->exchange($offer->getPayoutValue(), $offer->getPayoutCurrency(), \Traffic\Repository\CachedSettingsRepository::instance()->get("currency"));
            $rawClick->setRevenue($revenue);
            $payload->setRawClick($rawClick);
        }
        return $payload;
    }
}

?>