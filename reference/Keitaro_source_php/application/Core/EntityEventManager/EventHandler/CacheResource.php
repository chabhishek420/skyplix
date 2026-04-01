<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheResource implements EventHandlerInterface
{
    private $_entityName = NULL;
    public function __construct($type)
    {
        $this->_entityName = $type;
    }
    public function dataName()
    {
        return $this->_entityName;
    }
    public function handleUpdates(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        $this->_updateActive($storage, $ids);
    }
    public function handleDeletes(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        $this->_deleteDeleted($storage, $ids);
    }
    public function warmup(\Traffic\CachedData\Storage\StorageInterface $storage)
    {
        $this->_updateActive($storage);
    }
    private function _updateActive(\Traffic\CachedData\Storage\StorageInterface $storage, $ids = NULL)
    {
        $where = NULL;
        if (!empty($ids)) {
            $where = "id IN (" . implode(",", $ids) . ")";
        }
        $repository = \Traffic\CachedData\DataGetter\GetResource::definitionFor($this->_entityName)->repository();
        foreach ($repository->rawRows("*", $where) as $item) {
            $storage->set(\Traffic\CachedData\DataGetter\GetResource::cacheKey($this->_entityName, $item["id"]), $item);
        }
    }
    private function _deleteDeleted(\Traffic\CachedData\Storage\StorageInterface $storage, $ids)
    {
        foreach ($ids as $id) {
            $storage->delete(\Traffic\CachedData\DataGetter\GetResource::cacheKey($this->_entityName, $id));
        }
    }
}

?>