<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class UpdateStreamUniquenessSessionStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $campaign = $payload->getCampaign();
        $stream = $payload->getStream();
        $rawClick = $payload->getRawClick();
        $serverRequest = $payload->getServerRequest();
        if (empty($campaign)) {
            throw new StageException("Empty campaign");
        }
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        if (empty($stream)) {
            return $payload;
        }
        if ($rawClick->isBot()) {
            $rawClick->set("is_unique_stream", false);
            return $payload;
        }
        $rawClick->set("is_unique_stream", \Traffic\Session\Service\UniquenessSessionService::instance()->isUniqueForStream($serverRequest, $rawClick, $campaign, $stream));
        if (!$rawClick->isUniqueStream()) {
            $logEntry->add("Is not unique for stream");
        }
        return $payload;
    }
}

?>