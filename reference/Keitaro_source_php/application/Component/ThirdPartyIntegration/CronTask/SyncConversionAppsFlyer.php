<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\CronTask;

class SyncConversionAppsFlyer implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::OTHER;
    const INTERVAL_MINUTES = 1;
    public function run()
    {
        $fb = new \Component\ThirdPartyIntegration\AppsFlyer\AppsFlyer();
        $fb->syncConversions();
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " minutes");
        return $executedAt < $comparableDate;
    }
}

?>