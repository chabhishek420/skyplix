<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\CronTask;

class WarmupCacheTask implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::OTHER;
    const INTERVAL_MINUTES = 0;
    public function run()
    {
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
        \Traffic\CachedData\WarmupScheduler::done();
    }
    public function isReady(\DateTime $executedAt)
    {
        return \Traffic\CachedData\WarmupScheduler::isScheduled();
    }
}

?>