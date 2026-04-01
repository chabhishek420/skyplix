<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class StoreRawClicksStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $clicks = $payload->getRawClicksToStore();
        $logEntry->add("Saving clicks: " . count($clicks));
        if ($payload->getStream() && !$payload->getStream()->get("collect_clicks")) {
            $logEntry->add("Stream doesn't store clicks. Skipping.");
            return $payload;
        }
        if (\Traffic\Repository\CachedSettingsRepository::instance()->get("disable_stats")) {
            $logEntry->add("Statistics disabled. Skipping.");
            return $payload;
        }
        foreach ($clicks as $rawClick) {
            try {
                \Component\Clicks\DelayedCommand\AddClickCommand::saveClick($rawClick);
            } catch (\ADODB_Exception $e) {
                \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
            }
        }
        return $payload;
    }
}

?>