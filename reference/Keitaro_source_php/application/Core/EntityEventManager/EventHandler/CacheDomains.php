<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\EventHandler;

class CacheDomains implements EventHandlerInterface
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
        $where = "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        $domains = \Component\Domains\Repository\DomainsRepository::instance()->rawRows("*", $where);
        $storage->set(\Traffic\CachedData\DataGetter\GetDomains::CACHE_KEY, $domains);
    }
}

?>