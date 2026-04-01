<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cron\Model;

class CronTaskStatus extends \Core\Model\AbstractModel
{
    protected static $_fields = NULL;
    protected static $_tableName = "cron_status";
    public static function service()
    {
        return \Component\Cron\Service\CronTaskStatusService::instance();
    }
    public function getExecutedAt()
    {
        return $this->get("executed_at");
    }
}

?>