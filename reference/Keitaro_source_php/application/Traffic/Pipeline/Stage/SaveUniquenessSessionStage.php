<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class SaveUniquenessSessionStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $campaign = $payload->getCampaign();
        $stream = $payload->getStream();
        $rawClick = $payload->getRawClick();
        $request = $payload->getServerRequest();
        $response = $payload->getResponse();
        if (empty($request)) {
            throw new StageException("Error serverRequest is empty");
        }
        if (empty($response)) {
            throw new StageException("response is empty");
        }
        if (empty($campaign)) {
            throw new StageException("campaign is empty");
        }
        if (empty($rawClick)) {
            throw new StageException("rawClick is empty");
        }
        \Traffic\Session\Service\UniquenessSessionService::instance()->saveNonCookies($rawClick, $campaign, $stream, $logEntry);
        $payload->enableSaveUniquenessId();
        $payload->setServerRequest($request);
        $payload->setResponse($response);
        return $payload;
    }
}

?>