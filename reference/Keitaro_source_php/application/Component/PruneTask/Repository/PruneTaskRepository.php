<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\PruneTask\Repository;

class PruneTaskRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_pruneTasks = [];
    const ARCHIVE_TYPE = "archive";
    const GENERAL_TYPE = "general";
    const REFERENCE_TYPE = "reference";
    public function loadPruneTask()
    {
        if (!empty($this->_pruneTasks)) {
            return NULL;
        }
        \Core\ComponentManager\ComponentManager::instance()->loadPruneTask($this);
    }
    public function register(\Component\PruneTask\PruneTaskInterface $obj)
    {
        $type = $obj->getType();
        $this->_pruneTasks[$type][] = $obj;
    }
    public function getPruneTasks($type)
    {
        $this->loadPruneTask();
        if (empty($this->_pruneTasks[$type])) {
            throw new \Core\Registry\RegistryEntryNotFound("PruneTask type \"" . $type . "\" not found");
        }
        return $this->_pruneTasks[$type];
    }
}

?>