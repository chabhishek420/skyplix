<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class CheckSendingToAnotherCampaign implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        if ($payload->getActionType() === "campaign" || $payload->getActionType() === "group") {
            $payload->setForcedCampaignId($payload->getActionPayload());
            $payload->abort();
        }
        return $payload;
    }
}

?>