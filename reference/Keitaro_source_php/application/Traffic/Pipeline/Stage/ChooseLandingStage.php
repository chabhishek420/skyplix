<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class ChooseLandingStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $stream = $payload->getStream();
        $campaign = $payload->getCampaign();
        $rawClick = $payload->getRawClick();
        $currentLanding = $payload->getLanding();
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        if (empty($response)) {
            throw new StageException("response is not set");
        }
        if (empty($serverRequest)) {
            throw new StageException("serverRequest is not defined");
        }
        if (empty($stream)) {
            $logEntry->add("No stream, skip choosing landing");
            return $payload;
        }
        if ($stream->getSchema() != \Traffic\Model\BaseStream::LANDINGS && $stream->getSchema() != \Traffic\Model\BaseStream::OFFERS) {
            $logEntry->add("Schema is " . $stream->getSchema() . ", skip choosing landings");
            return $payload;
        }
        if (empty($campaign)) {
            throw new StageException("campaign is not defined");
        }
        if (empty($rawClick)) {
            throw new StageException("rawClick is not defined");
        }
        if (!empty($currentLanding)) {
            $logEntry->add("Landing is preselected #" . $currentLanding->getId());
            return $payload;
        }
        if (empty($landing)) {
            $rotator = new \Traffic\Actions\LandingOfferRotator(\Traffic\Pipeline\Service\EntityBindingService::TYPE_LANDING_BINDING, \Traffic\Repository\CachedLandingRepository::instance(), $campaign, $logEntry, $rawClick);
            $landingAssociations = \Traffic\Repository\CachedStreamLandingAssociationRepository::instance()->getCachedByStream($stream);
            if (empty($landingAssociations)) {
                $logEntry->add("No landings");
            } else {
                $landing = $rotator->getRandom($serverRequest, $landingAssociations, $logEntry);
                if ($campaign->isBindVisitorsLandingEnabled() && !empty($landing)) {
                    $rawClick = $payload->getRawClick();
                    $campaign = $payload->getCampaign();
                    $entityBinding = new \Traffic\Pipeline\Service\EntityBindingService($rawClick, $campaign, $logEntry);
                    $entityBinding->bindEntityRedis(\Traffic\Pipeline\Service\EntityBindingService::TYPE_LANDING_BINDING, $landing->getId());
                    $payload->enableCookieBindLanding();
                }
            }
        }
        if (!empty($landing)) {
            $logEntry->add("LP #" . $landing->getId() . " is chosen");
            $this->_updatePayload($payload, $landing);
        } else {
            $logEntry->add("No LP selected");
        }
        return $payload;
    }
    private function _updatePayload(\Traffic\Pipeline\Payload $payload, \Traffic\Model\Landing $landing)
    {
        $payload->setActionPayload($landing->getActionPayload());
        $payload->getRawClick()->setLandingId($landing->getId());
        $payload->setLanding($landing);
        $payload->setActionType($landing->getActionType());
        $payload->setActionOptions($landing->getActionOptions());
        if ($payload->getStream() && \Traffic\Repository\CachedStreamOfferAssociationRepository::instance()->hasCachedOffers($payload->getStream())) {
            $payload->setNeedToken(true);
            $payload->setAddTokenToUrl(true);
        }
    }
}

?>