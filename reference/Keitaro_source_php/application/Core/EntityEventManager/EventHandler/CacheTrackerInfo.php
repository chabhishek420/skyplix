<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheTrackerInfo implements EventHandlerInterface
{
    private $_trackerInfoRepository = NULL;
    public function __construct()
    {
        $this->_trackerInfoRepository = new \Component\System\Repository\TrackerInfoRepository();
    }
    public function handleUpdates(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        $this->warmup($storage);
    }
    public function handleDeletes(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        $this->warmup($storage);
    }
    public function warmup(\Traffic\CachedData\Storage\StorageInterface $storage)
    {
        $trackerInfo = $this->_trackerInfoRepository->info();
        $storage->set(\Traffic\CachedData\DataGetter\GetTrackerInfo::CACHE_KEY, $trackerInfo);
    }
}

?>