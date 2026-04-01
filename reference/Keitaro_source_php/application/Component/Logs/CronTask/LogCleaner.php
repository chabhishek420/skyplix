<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Logs\CronTask;

class LogCleaner implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const INTERVAL_MINUTES = 30;
    public function run()
    {
        \Traffic\Logging\Service\LoggerService::instance()->checkSize();
        \Core\Logging\Service\PostbackLoggerService::instance()->checkSize();
        \Core\Logging\Service\SentPostbackLoggerService::instance()->checkSize();
        \Traffic\Logging\Service\TrafficLoggerService::instance()->checkSize();
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " minutes");
        return empty($executedAt) || $executedAt < $comparableDate;
    }
}

?>