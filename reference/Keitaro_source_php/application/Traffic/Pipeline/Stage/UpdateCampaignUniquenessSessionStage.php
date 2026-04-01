<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class UpdateCampaignUniquenessSessionStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $campaign = $payload->getCampaign();
        $rawClick = $payload->getRawClick();
        $serverRequest = $payload->getServerRequest();
        if (empty($campaign)) {
            throw new StageException("Empty campaign");
        }
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        if ($rawClick->isBot()) {
            $rawClick->set("is_unique_campaign", false);
            return $payload;
        }
        $rawClick->set("is_unique_campaign", \Traffic\Session\Service\UniquenessSessionService::instance()->isUniqueForCampaign($serverRequest, $rawClick, $campaign));
        $rawClick->set("is_unique_global", \Traffic\Session\Service\UniquenessSessionService::instance()->isUniqueGlobal($serverRequest, $rawClick, $campaign));
        $logEntry->addLazy(function () {
            json_encode($serverRequest->getCookieParams());
        });
        if (!$rawClick->isUniqueCampaign()) {
            $logEntry->add("Is not unique for campaign");
        }
        return $payload;
    }
}

?>