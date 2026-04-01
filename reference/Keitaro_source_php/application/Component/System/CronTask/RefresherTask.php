<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\CronTask;

class RefresherTask implements \Component\Cron\CronTaskInterface
{
    const INTERVAL_DAYS = 31;
    public function run()
    {
        if (\Component\Cron\Service\CronService::instance()->wasExecuted(\Traffic\Tools\Tools::demodulize(get_called_class()))) {
            \Traffic\Service\SettingsService::instance()->updateValue(\Traffic\Model\Setting::SECURE_TRANSPORT, 1);
        }
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_DAYS . " days");
        return $executedAt < $comparableDate;
    }
}

?>