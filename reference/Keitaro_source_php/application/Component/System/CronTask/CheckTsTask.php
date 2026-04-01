<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\CronTask;

class CheckTsTask implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CHECKERS;
    const INTERVAL_HOURS = 2;
    public function run()
    {
        if (!\Core\Application\TsService::instance()->isTimestampActive()) {
            \Core\Application\EssentialService::instance()->checkIfTokenUpdated();
        }
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_HOURS . " hours");
        return $executedAt < $comparableDate;
    }
}

?>