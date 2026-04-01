<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\PruneTask;

abstract class BaseArchivePruneTask implements PruneTaskInterface
{
    private $_pruner = NULL;
    public function getType()
    {
        return Repository\PruneTaskRepository::ARCHIVE_TYPE;
    }
    public function setPruner(Pruner $pruner)
    {
        $this->_pruner = $pruner;
    }
    public function prune()
    {
        if (empty($this->_pruner)) {
            throw new \Core\Application\Exception\Error("Pruner is not set " . get_class($this));
        }
        $this->_pruner->prune();
    }
    public function deleteAll()
    {
        $this->_pruner->pruneAll();
    }
}

?>