<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheStream implements EventHandlerInterface
{
    public function handleUpdates(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        switch ($entityName) {
            case \Traffic\Model\Stream::entityName():
                return $this->_updateCache($storage, $ids);
                break;
            case \Traffic\Model\Campaign::entityName():
                $ids = \Component\Streams\Repository\StreamRepository::instance()->getIdsForCampaignIds($ids);
                return $this->_updateCache($storage, $ids);
                break;
            default:
                throw new \Exception("Incompatible with " . $entityName);
        }
    }
    public function handleDeletes(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        switch ($entityName) {
            case \Traffic\Model\Stream::entityName():
                return $this->_updateDeleted($storage, $ids);
                break;
            case \Traffic\Model\Campaign::entityName():
                $ids = \Component\Streams\Repository\StreamRepository::instance()->getIdsForCampaignIds($ids);
                return $this->_updateDeleted($storage, $ids);
                break;
            default:
                throw new \Exception("Incompatible with " . $entityName);
        }
    }
    public function warmup(\Traffic\CachedData\Storage\StorageInterface $storage)
    {
        $this->_updateCache($storage);
    }
    private function _updateCache(\Traffic\CachedData\Storage\StorageInterface $storage, $ids = NULL)
    {
        $where = NULL;
        if (!empty($ids)) {
            $where = "id IN (" . implode(",", $ids) . ")";
            $streams = \Component\Streams\Repository\StreamRepository::instance()->rawRows("*", $where);
        } else {
            $streamIds = \Component\Streams\Repository\StreamRepository::instance()->getAllIds();
            $streams = [];
            foreach (array_chunk($streamIds, 5) as $streamIdsChunked) {
                $where = "id IN (" . implode(",", $streamIdsChunked) . ")";
                $streams = array_merge($streams, \Component\Streams\Repository\StreamRepository::instance()->rawRows("*", $where));
            }
        }
        foreach ($streams as $row) {
            $key = \Traffic\CachedData\DataGetter\GetStream::cacheKey($row["id"]);
            $storage->set($key, $row);
        }
    }
    private function _updateDeleted(\Traffic\CachedData\Storage\StorageInterface $storage, $ids)
    {
        foreach ($ids as $id) {
            $key = \Traffic\CachedData\DataGetter\GetStream::cacheKey($id);
            $storage->delete($key);
        }
    }
}

?>