<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\CronTask;

class PruneOldFiles implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const INTERVAL_MINUTES = 60;
    public function run()
    {
        \Component\Clicks\Service\ExportedReportsService::instance()->pruneOldFiles(new \DateTime());
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " minutes");
        return $executedAt < $comparableDate;
    }
}

?>