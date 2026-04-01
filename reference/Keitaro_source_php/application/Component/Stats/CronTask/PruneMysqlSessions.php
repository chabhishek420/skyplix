<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Stats\CronTask;

class PruneMysqlSessions implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const INTERVAL_MINUTES = 360;
    public function run()
    {
        $storage = new \Traffic\Session\Storage\MysqlStorage(\Traffic\Request\ServerRequest::build());
        $storage->prune();
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " minutes");
        return empty($executedAt) || $executedAt < $comparableDate;
    }
}

?>