<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\CronTask;

class PruneReferences implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const INTERVAL_HOURS = 168;
    public function run()
    {
        \Component\Cleaner\DelayedCommand\PruneReferencesCommand::schedule();
    }
    public function isInHourRange(\DateTime $time, $hourBegin, $hourEnd)
    {
        $begin = clone $time;
        $begin->setTime($hourBegin, 0);
        $end = clone $time;
        $end->setTime($hourEnd, 0);
        return $begin <= $time && $time <= $end;
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_HOURS . " hours");
        $timezone = \Component\Users\Repository\UserRepository::instance()->getFirstAdminTimezone();
        return $executedAt < $comparableDate && $this->isInHourRange(new \DateTime("now", $timezone), 3, 4);
    }
}

?>