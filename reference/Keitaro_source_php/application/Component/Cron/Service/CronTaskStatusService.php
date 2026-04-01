<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cron\Service;

class CronTaskStatusService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Cron\Model\CronTaskStatus::definition();
    }
    public function updateStatus(\Component\Cron\Model\CronTaskStatus $taskStatus)
    {
        $taskStatus->set("executed_at", new \DateTime());
        if ($taskStatus->getId()) {
            return $this->save($taskStatus);
        }
        return $this->create($taskStatus->getData());
    }
}

?>