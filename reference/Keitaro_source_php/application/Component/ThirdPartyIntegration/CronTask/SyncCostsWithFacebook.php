<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\CronTask;

class SyncCostsWithFacebook implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::OTHER;
    const INTERVAL_MINUTES = 240;
    public function run()
    {
        $fb = new \Component\ThirdPartyIntegration\Facebook\Facebook();
        $fb->syncCostsAll();
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " minutes");
        return $executedAt < $comparableDate;
    }
}

?>