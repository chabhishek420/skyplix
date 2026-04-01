<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetResource implements DataGetterInterface
{
    private $_entityName = NULL;
    const AFFILIATE_NETWORK = "affiliate_network";
    const OFFER = "offer";
    const LANDING = "landing";
    const TRAFFIC_SOURCE = "ts";
    public function __construct($entityName)
    {
        $this->_entityName = $entityName;
    }
    public static function definitionFor($entityName)
    {
        switch ($entityName) {
            case \Traffic\Model\Landing::entityName():
                return \Traffic\Model\Landing::definition();
                break;
            case \Traffic\Model\Offer::entityName():
                return \Traffic\Model\Offer::definition();
                break;
            case \Traffic\Model\AffiliateNetwork::entityName():
                return \Traffic\Model\AffiliateNetwork::definition();
                break;
            case \Traffic\Model\TrafficSource::entityName():
                return \Traffic\Model\TrafficSource::definition();
                break;
            default:
                throw new \Exception("no repo for " . $entityName);
        }
    }
    public static function cacheKeys()
    {
        return [\Traffic\Model\Landing::entityName() => "LN", \Traffic\Model\Offer::entityName() => "OF", \Traffic\Model\AffiliateNetwork::entityName() => "AN", \Traffic\Model\TrafficSource::entityName() => "TS"];
    }
    public function name()
    {
        return $this->_entityName;
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        if (!is_numeric($scope)) {
            throw new \Exception("\$scope must be an ID");
        }
        $result = $storage->get(self::cacheKey($this->name(), $scope));
        $service = self::definitionFor($this->name())->service();
        return $service->restore($result);
    }
    public function fallback($scope = NULL)
    {
        return self::definitionFor($this->_entityName)->repository()->find($scope);
    }
    public static function cacheKey($entityName, $id)
    {
        if (empty(self::cacheKeys()[$entityName])) {
            throw new \Exception("No type " . $entityName);
        }
        return implode("_", [self::cacheKeys()[$entityName], $id]);
    }
}

?>