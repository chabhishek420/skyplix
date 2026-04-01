<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Archive\CronTask;

class PruneArchive implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const INTERVAL_HOURS = 24;
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_HOURS . " minutes");
        return empty($executedAt) || $executedAt < $comparableDate;
    }
    public function run()
    {
        $tasks = \Component\PruneTask\Repository\PruneTaskRepository::instance()->getPruneTasks(\Component\PruneTask\Repository\PruneTaskRepository::ARCHIVE_TYPE);
        foreach ($tasks as $task) {
            $task->prune();
        }
    }
}

?>