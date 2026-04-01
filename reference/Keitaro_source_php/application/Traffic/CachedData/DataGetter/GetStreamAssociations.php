<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetStreamAssociations implements DataGetterInterface
{
    private $_entityName = NULL;
    public static function cacheKeys()
    {
        return [\Traffic\Model\StreamLandingAssociation::entityName() => "S_L", \Traffic\Model\StreamOfferAssociation::entityName() => "S_O"];
    }
    public function __construct($entityName)
    {
        if (!isset(self::cacheKeys()[$entityName])) {
            throw new \Exception("incorrect entityName " . $entityName);
        }
        $this->_entityName = $entityName;
    }
    public static function definitionFor($entityName)
    {
        switch ($entityName) {
            case \Traffic\Model\StreamLandingAssociation::entityName():
                return \Traffic\Model\StreamLandingAssociation::definition();
                break;
            case \Traffic\Model\StreamOfferAssociation::entityName():
                return \Traffic\Model\StreamOfferAssociation::definition();
                break;
            default:
                throw new \Exception("not implemented for " . $entityName);
        }
    }
    public function name()
    {
        return $this->_entityName;
    }
    public function fallback($scope = NULL)
    {
        return self::definitionFor($this->_entityName)->repository()->all("stream_id = " . $scope["stream_id"]);
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        if (empty($scope["stream_id"])) {
            throw new \Exception("No 'stream_id'");
        }
        $key = self::cacheKey($this->_entityName, $scope["stream_id"]);
        $definition = self::definitionFor($this->_entityName);
        $service = $definition->service();
        $result = $storage->get($key);
        $associations = [];
        foreach ($result as $rawData) {
            $associations[] = $service->restore($rawData);
        }
        return $associations;
    }
    public static function cacheKey($entityName, $streamId)
    {
        return implode("_", [self::cacheKeys()[$entityName], $streamId]);
    }
}

?>