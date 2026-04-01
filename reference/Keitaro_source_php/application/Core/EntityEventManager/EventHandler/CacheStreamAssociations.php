<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheStreamAssociations implements EventHandlerInterface
{
    private $_entityName = NULL;
    public function __construct($entityName)
    {
        $this->_entityName = $entityName;
    }
    public function handleUpdates(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        switch ($entityName) {
            case \Traffic\Model\Stream::entityName():
                return $this->_updateAssociationsFor($storage, $ids);
                break;
            default:
                throw new \Exception("not implemented for " . $entityName);
        }
    }
    public function handleDeletes(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        switch ($entityName) {
            case \Traffic\Model\Stream::entityName():
                return $this->_deleteAssociationsFor($storage, $ids);
                break;
            default:
                throw new \Exception("not implemented for " . $entityName);
        }
    }
    public function warmup(\Traffic\CachedData\Storage\StorageInterface $storage)
    {
        $ids = \Component\Streams\Repository\StreamRepository::instance()->pluck("state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE), "id");
        if (!empty($ids)) {
            $this->_updateAssociationsFor($storage, $ids);
        }
    }
    private function _updateAssociationsFor(\Traffic\CachedData\Storage\StorageInterface $storage, $streamIds = [])
    {
        if (empty($streamIds)) {
            throw new \Exception("streamIds must not be empty");
        }
        $activeStreamIds = $this->_activeStreamIds($streamIds);
        $groupedAssociations = $this->_groupedAssociations($streamIds);
        foreach ($activeStreamIds as $streamId) {
            $associations = empty($groupedAssociations[$streamId]) ? [] : $groupedAssociations[$streamId];
            $key = \Traffic\CachedData\DataGetter\GetStreamAssociations::cacheKey($this->_entityName, $streamId);
            $storage->set($key, $associations);
        }
    }
    private function _activeStreamIds($streamIds)
    {
        $where = "id in (" . implode(", ", $streamIds) . ") and state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        return \Component\Streams\Repository\StreamRepository::instance()->pluck($where, "id");
    }
    private function _groupedAssociations($streamIds)
    {
        $where = "stream_id IN (" . implode(",", $streamIds) . ")";
        $definition = \Traffic\CachedData\DataGetter\GetStreamAssociations::definitionFor($this->_entityName);
        $associations = $definition->repository()->rawRows("*", $where);
        $groupedAssociations = [];
        foreach ($associations as $assoc) {
            if (!isset($groupedAssociations[$assoc["stream_id"]])) {
                $groupedAssociations[$assoc["stream_id"]] = [];
            }
            $groupedAssociations[$assoc["stream_id"]][] = $assoc;
        }
        return $groupedAssociations;
    }
    private function _deleteAssociationsFor(\Traffic\CachedData\Storage\StorageInterface $storage, $streamIds)
    {
        foreach ($streamIds as $streamId) {
            $key = \Traffic\CachedData\DataGetter\GetStreamAssociations::cacheKey($this->_entityName, $streamId);
            $storage->delete($key);
        }
    }
}

?>