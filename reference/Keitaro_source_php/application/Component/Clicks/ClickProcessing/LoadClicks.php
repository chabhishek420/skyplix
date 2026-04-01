<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class LoadClicks implements StageInterface
{
    public function process($entries)
    {
        $clicks = [];
        foreach ($entries as $update) {
            if (empty($update["sub_id"])) {
                $update = json_encode($update, JSON_PARTIAL_OUTPUT_ON_ERROR);
                $this->_error("[Update command] sub_id not provided: " . $update);
            } else {
                if (isset($clicks[$update["sub_id"]])) {
                    $click = $clicks[$update["sub_id"]];
                } else {
                    $click = \Traffic\Model\Click::fetchRow("*", "`sub_id` = " . \Core\Db\Db::quote($update["sub_id"]));
                }
                if (empty($click)) {
                    if (!\Traffic\CommandQueue\Service\DelayedCommandService::instance()->retry($update)) {
                        $update = json_encode($update, JSON_PARTIAL_OUTPUT_ON_ERROR);
                        $this->_error("[Update command] click not found: " . $update);
                    }
                } else {
                    $click = array_merge($click, $update);
                    $clicks[$click["sub_id"]] = $click;
                }
            }
        }
        return array_values($clicks);
    }
    private function _error($error)
    {
        if (\Core\Application\Application::instance()->isProduction()) {
            \Traffic\Logging\Service\LoggerService::instance()->error($error);
        } else {
            throw new \Core\Application\Exception\Error($error);
        }
    }
}

?>