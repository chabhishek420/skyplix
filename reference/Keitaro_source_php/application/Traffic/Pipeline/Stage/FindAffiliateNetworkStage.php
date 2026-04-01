<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class FindAffiliateNetworkStage implements StageInterface
{
    const ACTION_TYPE_WITHOUT_PARAMS = NULL;
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $offer = $payload->getOffer();
        if (empty($offer)) {
            return $payload;
        }
        if ($offer->getAffiliateNetworkId()) {
            $network = \Traffic\Repository\CachedAffiliateNetworkRepository::instance()->findCached($offer->getAffiliateNetworkId());
        }
        if (!empty($network)) {
            if (!in_array($offer->getActionType(), ACTION_TYPE_WITHOUT_PARAMS)) {
                $actionPayload = \Component\Offers\Service\OfferService::instance()->addParameterToUrl($payload->getActionPayload(), $network->get("offer_param"));
                $payload->setActionPayload($actionPayload);
            }
            $payload->getRawClick()->setAffiliateNetworkId($network->getId());
        }
        return $payload;
    }
}

?>