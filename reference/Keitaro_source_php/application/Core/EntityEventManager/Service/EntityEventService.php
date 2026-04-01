<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager\Service;

class EntityEventService extends \Traffic\Service\AbstractService
{
    private $_storage = NULL;
    private $_subscriptions = [];
    private $_eventsCollection = NULL;
    const LOCK_NAME = "data_replicator";
    public function __construct()
    {
        $this->setEventsCollection(new \Core\EntityEventManager\EventCollection());
        $this->_subscribe(\Traffic\Model\Campaign::entityName(), [new \Core\EntityEventManager\EventHandler\CacheCampaignAliases(), new \Core\EntityEventManager\EventHandler\CacheCampaignTokens(), new \Core\EntityEventManager\EventHandler\CacheCampaign(), new \Core\EntityEventManager\EventHandler\CacheStreams()]);
        $this->_subscribe(\Traffic\Model\Stream::entityName(), [new \Core\EntityEventManager\EventHandler\CacheStreams(), new \Core\EntityEventManager\EventHandler\CacheStream(), new \Core\EntityEventManager\EventHandler\CacheStreamFilters(), new \Core\EntityEventManager\EventHandler\CacheStreamAssociations(\Traffic\Model\StreamLandingAssociation::entityName()), new \Core\EntityEventManager\EventHandler\CacheStreamAssociations(\Traffic\Model\StreamOfferAssociation::entityName())]);
        $this->_subscribe(\Traffic\Model\TrafficSource::entityName(), [new \Core\EntityEventManager\EventHandler\CacheResource(\Traffic\Model\TrafficSource::entityName())]);
        $this->_subscribe(\Traffic\Model\AffiliateNetwork::entityName(), [new \Core\EntityEventManager\EventHandler\CacheResource(\Traffic\Model\AffiliateNetwork::entityName())]);
        $this->_subscribe(\Traffic\Model\Landing::entityName(), [new \Core\EntityEventManager\EventHandler\CacheResource(\Traffic\Model\Landing::entityName()), new \Core\EntityEventManager\EventHandler\CacheLandingsUrls()]);
        $this->_subscribe(\Traffic\Model\Offer::entityName(), [new \Core\EntityEventManager\EventHandler\CacheResource(\Traffic\Model\Offer::entityName())]);
        $this->_subscribe(\Traffic\Model\Domain::entityName(), [new \Core\EntityEventManager\EventHandler\CacheDomains()]);
        $this->_subscribe(\Component\Users\Model\User::entityName(), [new \Core\EntityEventManager\EventHandler\CacheTrackerInfo()]);
    }
    public function add($eventName, $entityName, $entityId)
    {
        if ($this->getHandlers($entityName)) {
            \Traffic\Logging\Service\LoggerService::instance()->debug("[EntityEventService] " . $eventName . ", " . $entityName . ", " . $entityId . "}");
            $this->_eventsCollection->add($eventName, $entityName, $entityId);
        }
    }
    public function emitEvents()
    {
        if (!$this->getStorage()) {
            throw new \Exception("No storage is set");
        }
        $lock = \Core\Lock\LockService::instance()->tryLock(LOCK_NAME);
        if (!$lock) {
            \Traffic\Logging\Service\LoggerService::instance()->info("Lock caught on EventHandlerService. Scheduling a warmup.");
            \Traffic\CachedData\WarmupScheduler::scheduleWarmup();
        } else {
            try {
                $groupedEvents = $this->eventsCollection()->groupedEvents();
                \Traffic\Logging\Service\LoggerService::instance()->debug(function () {
                    return "Emit events: " . json_encode($groupedEvents);
                });
                foreach ($groupedEvents as $entityName => $eventsByName) {
                    $handlers = $this->getHandlers($entityName);
                    if (!empty($handlers)) {
                        foreach ($handlers as $handler) {
                            if (!empty($eventsByName[\Core\EntityEventManager\Event::UPDATE])) {
                                $handler->handleUpdates($this->getStorage(), $entityName, $eventsByName[\Core\EntityEventManager\Event::UPDATE]);
                            }
                            if (!empty($eventsByName[\Core\EntityEventManager\Event::DELETE])) {
                                $handler->handleDeletes($this->getStorage(), $entityName, $eventsByName[\Core\EntityEventManager\Event::DELETE]);
                            }
                        }
                    }
                }
            } finally {
                $this->getStorage()->commit();
                $this->eventsCollection()->flush();
                \Core\Lock\LockService::instance()->unlock($lock, LOCK_NAME);
            }
        }
    }
    public function flush()
    {
        $this->_eventsCollection->flush();
    }
    public function setEventsCollection(\Core\EntityEventManager\EventCollection $collector)
    {
        $this->_eventsCollection = $collector;
    }
    public function eventsCollection()
    {
        return $this->_eventsCollection;
    }
    public function setStorage(\Traffic\CachedData\Storage\StorageInterface $storage)
    {
        $this->_storage = $storage;
        return $this;
    }
    public function getStorage()
    {
        return $this->_storage;
    }
    public function getSubscriptions()
    {
        return $this->_subscriptions;
    }
    public function getHandlers($entityName)
    {
        return isset($this->_subscriptions[$entityName]) ? $this->_subscriptions[$entityName] : NULL;
    }
    private function _subscribe($eventName, $handlers)
    {
        if (empty($eventName)) {
            throw new \Exception("entityName must be set");
        }
        if (!empty($this->_subscriptions[$eventName])) {
            throw new \Exception("Can't subscribe on '" . $eventName . "'. There already exists some subscriptions.");
        }
        $this->_subscriptions[$eventName] = $handlers;
        return $this;
    }
}

?>