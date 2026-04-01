<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers\CronTask;

class DeleteOldTriggers implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::CLEANERS;
    const INTERVAL_MINUTES = 24;
    public function run()
    {
        $triggersTable = \Component\Triggers\Model\TriggerAssociation::getTableName();
        $streamsTableName = \Traffic\Model\BaseStream::getTableName();
        $sql = "DELETE FROM " . $triggersTable . " WHERE stream_id NOT IN (\n          SELECT id FROM " . $streamsTableName . "\n        )";
        \Core\Db\Db::instance()->execute($sql);
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " hours");
        return $executedAt < $comparableDate;
    }
}

?>