<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cron\Repository;

class CronTaskStatusRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Cron\Model\CronTaskStatus::definition();
    }
}

?>