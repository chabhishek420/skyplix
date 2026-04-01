<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheCampaignAliases implements EventHandlerInterface
{
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
        $hash = \Traffic\CachedData\DataGetter\GetCampaignAliases::buildHash();
        $storage->set(\Traffic\CachedData\DataGetter\GetCampaignAliases::CACHE_KEY, $hash);
    }
}

?>