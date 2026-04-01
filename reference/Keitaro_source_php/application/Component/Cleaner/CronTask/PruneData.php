<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cleaner\CronTask;

class PruneData implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const TASKS_PRUNE_PERIOD = 10;
    const INTERVAL_MINUTES = 1440;
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " minutes");
        return empty($executedAt) || $executedAt < $comparableDate;
    }
    public function run()
    {
        $tasks = \Component\PruneTask\Repository\PruneTaskRepository::instance()->getPruneTasks(\Component\PruneTask\Repository\PruneTaskRepository::GENERAL_TYPE);
        foreach ($tasks as $task) {
            $task->prune();
        }
    }
}

?>