<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class ChooseOfferStage implements StageInterface
{
    const VERSION_SEND_TOKEN_ONLY = 2;
    const IGNORE_OFFER_PARAM = "exit";
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $stream = $payload->getStream();
        $campaign = $payload->getCampaign();
        $rawClick = $payload->getRawClick();
        $landing = $payload->getLanding();
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        if (empty($response)) {
            throw new StageException("response is not set");
        }
        if (empty($serverRequest)) {
            throw new StageException("serverRequest is empty");
        }
        if (empty($stream)) {
            $logEntry->add("No stream, skip choosing offer");
            return $payload;
        }
        if (empty($campaign)) {
            throw new StageException("Empty campaign");
        }
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        if (!empty($landing) && !$payload->isForceChooseOffer()) {
            $logEntry->add("Landing is chosen, skip choosing offer");
            return $payload;
        }
        if ($stream->getSchema() != \Traffic\Model\BaseStream::LANDINGS && $stream->getSchema() != \Traffic\Model\BaseStream::OFFERS) {
            $logEntry->add("Schema is '" . $stream->getSchema() . "' so offer is not needed.");
            return $payload;
        }
        $hasForcedOffer = \Traffic\Repository\CachedStreamOfferAssociationRepository::instance()->cachedStreamHasOfferId($stream, $payload->getForcedOfferId());
        if ($payload->getForcedOfferId() && $hasForcedOffer) {
            $logEntry->add("Loading offer #" . $payload->getForcedOfferId());
            $offer = \Traffic\Repository\CachedOfferRepository::instance()->findCached($payload->getForcedOfferId());
        }
        if (empty($offer)) {
            $rotator = new \Traffic\Actions\LandingOfferRotator(\Traffic\Pipeline\Service\EntityBindingService::TYPE_OFFER_BINDING, \Traffic\Repository\CachedOfferRepository::instance(), $campaign, $logEntry, $rawClick);
            $offerAssociations = \Traffic\Repository\CachedStreamOfferAssociationRepository::instance()->getCachedByStream($stream, false);
            if (empty($offerAssociations)) {
                $logEntry->add("No offers in the stream");
            } else {
                $offer = $rotator->getRandom($serverRequest, $offerAssociations, $logEntry);
                if (empty($offer)) {
                    $logEntry->add("Rotator return empty result");
                }
                if ($campaign->isBindVisitorsOfferEnabled() && !empty($offer)) {
                    $rawClick = $payload->getRawClick();
                    $campaign = $payload->getCampaign();
                    $entityBinding = new \Traffic\Pipeline\Service\EntityBindingService($rawClick, $campaign, $logEntry);
                    $entityBinding->bindEntityRedis(\Traffic\Pipeline\Service\EntityBindingService::TYPE_OFFER_BINDING, $offer->getId());
                    $payload->enableCookieBindOffer();
                }
            }
        }
        if (empty($offer)) {
            $logEntry->add("No offer is chosen");
            return $payload;
        }
        $newOffer = \Component\Conversions\ConversionCapacity\Service\ConversionCapacityService::instance()->findAvailableOffer($offer);
        if ($newOffer->getId() != $offer->getId()) {
            $logEntry->add("Offer #" . $offer->getId() . " reach his conversion capacity. An alternative is chosen #" . $newOffer->getId());
            $offer = $newOffer;
        }
        if (empty($offer)) {
            $logEntry->add("Warning! Offer #" . $offer->getId() . " doesn't have alternative offer in his chain.");
            return $payload;
        }
        $logEntry->add("Offer #" . $offer->getId() . " is chosen");
        if ($payload->getServerRequest()->getParam(IGNORE_OFFER_PARAM) != 1) {
            $payload->getRawClick()->setOfferId($offer->getId());
        }
        $payload->setOffer($offer);
        $payload->setNeedToken(true);
        if ($payload->isForceRedirectOffer()) {
            $payload->setActionType($offer->getActionType());
            $payload->setActionPayload($offer->getActionPayload());
            $payload->setActionOptions($offer->getActionOptions());
        }
        return $payload;
    }
}

?>