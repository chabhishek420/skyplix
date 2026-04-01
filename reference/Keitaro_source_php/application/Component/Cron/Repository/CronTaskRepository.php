<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cron\Repository;

class CronTaskRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_cronTasks = [];
    public function loadCronTasks()
    {
        if (!empty($this->_cronTasks)) {
            return NULL;
        }
        \Core\ComponentManager\ComponentManager::instance()->loadCronTasks($this);
    }
    public function register($obj, $highPriority = false)
    {
        if ($highPriority) {
            array_unshift($this->_cronTasks, $obj);
        } else {
            $this->_cronTasks[] = $obj;
        }
    }
    public function getCronTasks()
    {
        $this->loadCronTasks();
        return $this->_cronTasks;
    }
}

?>