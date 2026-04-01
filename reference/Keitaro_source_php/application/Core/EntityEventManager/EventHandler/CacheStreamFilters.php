<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheStreamFilters implements EventHandlerInterface
{
    public function handleUpdates(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids = NULL)
    {
        switch ($entityName) {
            case \Traffic\Model\Stream::entityName():
                return $this->_updateCacheForStreamIds($storage, $ids);
                break;
            default:
                throw new \Exception("not implemented for " . $entityName);
        }
    }
    public function handleDeletes(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        switch ($entityName) {
            case \Traffic\Model\Stream::entityName():
                return $this->_deleteForStreamIds($storage, $ids);
                break;
            default:
                throw new \Exception("not implemented for " . $entityName);
        }
    }
    public function warmup(\Traffic\CachedData\Storage\StorageInterface $storage)
    {
        $streamIds = \Component\Streams\Repository\StreamRepository::instance()->pluck("state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE), "id");
        if (empty($streamIds)) {
            return NULL;
        }
        $this->_updateCacheForStreamIds($storage, $streamIds);
    }
    private function _updateCacheForStreamIds(\Traffic\CachedData\Storage\StorageInterface $storage, $streamIds)
    {
        if (empty($streamIds)) {
            throw new \Exception("\$streamIds is empty");
        }
        $activeStreamIds = $this->_activeStreamIds($streamIds);
        $groupedFilters = $this->_groupedFilters($streamIds);
        foreach ($activeStreamIds as $streamId) {
            $filters = empty($groupedFilters[$streamId]) ? [] : $groupedFilters[$streamId];
            $key = \Traffic\CachedData\DataGetter\GetStreamFilters::cacheKey($streamId);
            $storage->set($key, $filters);
        }
    }
    private function _activeStreamIds($streamIds)
    {
        $where = "id in (" . implode(", ", $streamIds) . ") and state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        return \Component\Streams\Repository\StreamRepository::instance()->pluck($where, "id");
    }
    private function _groupedFilters($streamIds)
    {
        $where = "stream_id IN (" . implode(", ", \Core\Db\Db::quote($streamIds)) . ")";
        $filters = \Component\StreamFilters\Repository\StreamFilterRepository::instance()->rawRows("*", $where);
        $groupedFilters = [];
        foreach ($filters as $filter) {
            if (!isset($groupedFilters[$filter["stream_id"]])) {
                $groupedFilters[$filter["stream_id"]] = [];
            }
            $groupedFilters[$filter["stream_id"]][] = $filter;
        }
        return $groupedFilters;
    }
    private function _deleteForStreamIds(\Traffic\CachedData\Storage\StorageInterface $storage, $streamIds)
    {
        foreach ($streamIds as $streamId) {
            $key = \Traffic\CachedData\DataGetter\GetStreamFilters::cacheKey($streamId);
            $storage->delete($key);
        }
    }
}

?>