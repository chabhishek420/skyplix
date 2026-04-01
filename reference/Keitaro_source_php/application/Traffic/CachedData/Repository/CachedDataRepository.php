<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\Repository;

class CachedDataRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_dataGetters = [];
    private $_storage = NULL;
    public function __construct()
    {
        $this->register(new \Traffic\CachedData\DataGetter\GetCampaignAliases());
        $this->register(new \Traffic\CachedData\DataGetter\GetCampaignTokens());
        $this->register(new \Traffic\CachedData\DataGetter\GetCampaign());
        $this->register(new \Traffic\CachedData\DataGetter\GetStreams());
        $this->register(new \Traffic\CachedData\DataGetter\GetStream());
        $this->register(new \Traffic\CachedData\DataGetter\GetResource(\Traffic\Model\Landing::entityName()));
        $this->register(new \Traffic\CachedData\DataGetter\GetResource(\Traffic\Model\Offer::entityName()));
        $this->register(new \Traffic\CachedData\DataGetter\GetResource(\Traffic\Model\TrafficSource::entityName()));
        $this->register(new \Traffic\CachedData\DataGetter\GetResource(\Traffic\Model\AffiliateNetwork::entityName()));
        $this->register(new \Traffic\CachedData\DataGetter\GetDomains());
        $this->register(new \Traffic\CachedData\DataGetter\GetStreamAssociations(\Traffic\Model\StreamLandingAssociation::entityName()));
        $this->register(new \Traffic\CachedData\DataGetter\GetStreamAssociations(\Traffic\Model\StreamOfferAssociation::entityName()));
        $this->register(new \Traffic\CachedData\DataGetter\GetStreamFilters());
        $this->register(new \Traffic\CachedData\DataGetter\GetLandingsUrls());
        $this->register(new \Traffic\CachedData\DataGetter\GetTrackerInfo());
    }
    public function warmup()
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[CachedDataRepository] warmup");
        $warmedUp = [];
        foreach (\Core\EntityEventManager\Service\EntityEventService::instance()->getSubscriptions() as $entityName => $handlers) {
            foreach ($handlers as $handler) {
                if (!in_array($handler, $warmedUp)) {
                    $handler->warmup($this->getStorage());
                    $warmedUp[] = $handler;
                }
            }
        }
        $this->getStorage()->commit();
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
    public function register(\Traffic\CachedData\DataGetter\DataGetterInterface $getter)
    {
        $dataName = $getter->name();
        if (!empty($this->_dataGetters[$dataName])) {
            throw new \Exception("Data name " . $dataName . " already have a getter " . get_class($this->_dataGetters[$dataName]));
        }
        $this->_dataGetters[$dataName] = $getter;
    }
    public function get($getterName, $scope = NULL, $useFallbackStrategy = true)
    {
        while (!$this->getStorage()) {
            $getter = $this->getGetter($getterName);
            try {
                return $getter->get($this->getStorage(), $scope);
            } catch (\Traffic\Cache\NoCache $e) {
                if (!$useFallbackStrategy) {
                    throw $e;
                }
                if (!$this->getStorage() instanceof \Traffic\CachedData\Storage\NullStorage) {
                    $cacheName = \Traffic\Tools\Tools::demodulize(get_class($this->getStorage()));
                    $msg = "[" . $cacheName . "]  " . $e->getMessage() . ". That's ok, we're loading data from the DB.";
                    \Traffic\Logging\Service\LoggerService::instance()->warning($msg);
                    \Traffic\CachedData\WarmupScheduler::scheduleWarmup();
                }
                if (\Core\Db\Db::instance()->isEnabled()) {
                    return $getter->fallback($scope);
                }
                throw $e;
            }
        }
        throw new \Exception("No storage is set");
    }
    public function exists($getterName, $scope = NULL)
    {
        return $this->get($getterName, $scope);
    }
    public function getGetter($dataName)
    {
        if (!isset($this->_dataGetters[$dataName])) {
            throw new \Exception("No registered getters for data name '" . $dataName . "'");
        }
        return $this->_dataGetters[$dataName];
    }
    public function deleteAll()
    {
        $this->_storage->deleteAll();
    }
}

?>