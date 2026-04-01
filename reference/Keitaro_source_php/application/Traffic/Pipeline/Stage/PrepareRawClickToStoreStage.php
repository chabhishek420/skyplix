<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class PrepareRawClickToStoreStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $rawClick = $payload->getRawClick();
        $stream = $payload->getStream();
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        if ($stream && !$stream->get("collect_clicks")) {
            return $payload;
        }
        if (\Traffic\Repository\CachedSettingsRepository::instance()->get("disable_stats", false)) {
            return $payload;
        }
        $payload->addRawClickToStore($rawClick);
        return $payload;
    }
}

?>