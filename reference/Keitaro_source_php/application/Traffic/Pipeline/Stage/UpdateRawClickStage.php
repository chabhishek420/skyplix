<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class UpdateRawClickStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $rawClick = $payload->getRawClick();
        $campaign = $payload->getCampaign();
        if (empty($rawClick)) {
            throw new StageException("rawClick is not set");
        }
        if (empty($campaign)) {
            throw new StageException("campaign is not set");
        }
        $rawClick->setCampaignId($campaign->getId());
        $trafficSourceId = $campaign->get("traffic_source_id");
        $rawClick->set("ts_id", $trafficSourceId);
        $rawClick->setVisitorCode(\Traffic\Service\VisitorService::instance()->generateCode($rawClick));
        $rawClick->setSubId(\Traffic\Service\RawClickService::instance()->generate($rawClick->getVisitorCode()));
        $payload->setRawClick($rawClick);
        return $payload;
    }
}

?>