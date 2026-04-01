<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cron\Service;

class CronService extends \Traffic\Service\AbstractService
{
    public function getLastRun()
    {
        $task = \Component\Cron\Repository\CronTaskStatusRepository::instance()->findFirst(NULL, "executed_at DESC");
        if ($task) {
            return $task->get("executed_at");
        }
        return NULL;
    }
    public function wasExecuted($taskName)
    {
        $where = "task_name = " . \Core\Db\Db::quote($taskName);
        return \Component\Cron\Repository\CronTaskStatusRepository::instance()->exists($where);
    }
    public function getCurrentTaskStatus($taskName)
    {
        $status = \Component\Cron\Repository\CronTaskStatusRepository::instance()->findFirst("task_name = " . \Core\Db\Db::quote($taskName));
        if (empty($status)) {
            $status = CronTaskStatusService::instance()->build(["task_name" => $taskName, "executed_at" => new \DateTime("-999 days")]);
        }
        return $status;
    }
}

?>