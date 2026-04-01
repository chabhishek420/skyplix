<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheStreams implements EventHandlerInterface
{
    public function handleUpdates(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        switch ($entityName) {
            case \Traffic\Model\Stream::entityName():
                $campaignIds = \Component\Streams\Repository\StreamRepository::instance()->getCampaignIdsForIds($ids);
                if (empty($campaignIds)) {
                    return NULL;
                }
                return $this->_updateForCampaignIds($storage, $campaignIds);
                break;
            case \Traffic\Model\Campaign::entityName():
            default:
                throw new \Exception("not implemented for " . $entityName);
        }
    }
    public function handleDeletes(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        switch ($entityName) {
            case \Traffic\Model\Stream::entityName():
                $campaignIds = \Component\Streams\Repository\StreamRepository::instance()->getCampaignIdsForIds($ids);
                if (empty($campaignIds)) {
                    return NULL;
                }
                return $this->_removeForCampaignIds($storage, $campaignIds);
                break;
            case \Traffic\Model\Campaign::entityName():
                return $this->_removeForCampaignIds($storage, $ids);
                break;
            default:
                throw new \Exception("not implemented for " . $entityName);
        }
    }
    public function warmup(\Traffic\CachedData\Storage\StorageInterface $storage)
    {
        $campaignIds = \Component\Campaigns\Repository\CampaignRepository::instance()->pluck("state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE), "id");
        if (!empty($campaignIds)) {
            $this->_updateForCampaignIds($storage, $campaignIds);
        }
    }
    private function _updateForCampaignIds(\Traffic\CachedData\Storage\StorageInterface $storage, $campaignIds)
    {
        if (empty($campaignIds)) {
            throw new \Exception("\$campaignIds is empty");
        }
        $groupedStreams = [];
        foreach (array_chunk($campaignIds, 5) as $campaignIdsChunk) {
            $groupedStreams += \Traffic\CachedData\DataGetter\GetStreams::getGroupedStreams($campaignIdsChunk);
        }
        foreach ($campaignIds as $campaignId) {
            $streams = isset($groupedStreams[$campaignId]) ? $groupedStreams[$campaignId] : [];
            $key = \Traffic\CachedData\DataGetter\GetStreams::cacheKey($campaignId);
            $storage->set($key, $streams);
        }
    }
    private function _removeForCampaignIds(\Traffic\CachedData\Storage\StorageInterface $storage, $campaignIds)
    {
        foreach ($campaignIds as $campaignId) {
            $key = \Traffic\CachedData\DataGetter\GetStreams::cacheKey($campaignId);
            $storage->delete($key);
        }
    }
}

?>