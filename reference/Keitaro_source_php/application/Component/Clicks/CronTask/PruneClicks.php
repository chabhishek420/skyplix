<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\CronTask;

class PruneClicks implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const INTERVAL_HOURS = 24;
    public function run()
    {
        $statsTTL = \Traffic\Repository\CachedSettingsRepository::instance()->get("stats_ttl");
        if (empty($statsTTL) || $statsTTL == 0) {
            return NULL;
        }
        \Component\Cleaner\Service\CleanerService::instance()->pruneClicks($statsTTL);
    }
    public function isReady(\DateTime $executedAt)
    {
        if (\Component\System\Service\StatusService::instance()->areStatTablesLocked()) {
            return false;
        }
        $comparableDate = new \DateTime("-" . INTERVAL_HOURS . " hours");
        return $executedAt < $comparableDate;
    }
}

?>