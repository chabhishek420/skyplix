<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\DelayedCommands\CronTask;

class ExecuteDelayedCommand implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::STATISTICS;
    const LOCK_NAME = "delayed_command";
    public function run()
    {
        $lock = \Core\Lock\LockService::instance()->tryLock(LOCK_NAME);
        if (!$lock) {
            \Traffic\Logging\Service\LoggerService::instance()->info("Migration in progress. Processing delayed commands skipped.");
        } else {
            $case = new \Component\DelayedCommands\Processor\ProcessCommandQueue();
            $case->process(\Traffic\CommandQueue\Service\DelayedCommandService::instance());
            \Core\Lock\LockService::instance()->unlock($lock, LOCK_NAME);
        }
    }
    public function isReady(\DateTime $executedAt)
    {
        if (\Component\System\Service\StatusService::instance()->areStatTablesLocked()) {
            return false;
        }
        return true;
    }
}

?>