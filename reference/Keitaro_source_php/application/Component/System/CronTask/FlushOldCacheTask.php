<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\CronTask;

class FlushOldCacheTask implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const MULTICHANNEL_VERSION_SUPPORT = "1.15";
    const INTERVAL_DAYS = 7;
    public function run()
    {
        \Traffic\Cache\CacheService::instance()->flushOldNamespacedCache();
    }
    public function isReady(\DateTime $executedAt)
    {
        if (version_compare(\Component\System\Service\StatusService::instance()->getCurrentVersion(), MULTICHANNEL_VERSION_SUPPORT) < 0) {
            return false;
        }
        $comparableDate = new \DateTime("-" . INTERVAL_DAYS . " days");
        return $executedAt < $comparableDate;
    }
}

?>