<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheCampaign implements EventHandlerInterface
{
    public function handleUpdates(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        if (empty($ids)) {
            throw new \Exception("empty ids");
        }
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        $where .= " AND id IN (" . implode(",", $ids) . ")";
        $rows = \Component\Campaigns\Repository\CampaignRepository::instance()->rawRows("*", $where);
        $this->_cacheItems($storage, $rows);
    }
    public function handleDeletes(\Traffic\CachedData\Storage\StorageInterface $storage, $entityName, $ids)
    {
        foreach ($ids as $id) {
            $storage->delete(\Traffic\CachedData\DataGetter\GetCampaign::cacheKey($id));
        }
    }
    public function warmup(\Traffic\CachedData\Storage\StorageInterface $storage)
    {
        $rows = \Component\Campaigns\Repository\CampaignRepository::instance()->rawRows();
        $this->_cacheItems($storage, $rows);
    }
    private function _cacheItems(\Traffic\CachedData\Storage\StorageInterface $storage, $rows)
    {
        foreach ($rows as $row) {
            $storage->set(\Traffic\CachedData\DataGetter\GetCampaign::cacheKey($row["id"]), $row);
        }
    }
}

?>