<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers\CronTask;

class RunTriggersTask implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CHECKERS;
    public function run()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            return NULL;
        }
        $triggers = \Component\Triggers\Repository\TriggersRepository::instance()->allWithActiveStream();
        \Component\Av\Service\AVCheckerService::instance()->preload($triggers);
        foreach ($triggers as $trigger) {
            $checker = new \Component\Triggers\CheckTrigger($trigger);
            $checker->check();
        }
    }
    public function isReady(\DateTime $executedAt)
    {
        return true;
    }
}

?>