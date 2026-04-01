<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetTrackerInfo implements DataGetterInterface
{
    const NAME = "tracker_info";
    const CACHE_KEY = "TRINFO";
    public function name($name)
    {
        return NAME;
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        if (!empty($scope)) {
            throw new \Exception("scope is not implemented");
        }
        return $storage->get(CACHE_KEY);
    }
    public function fallback($fallback = NULL, $scope)
    {
        return (new \Component\System\Repository\TrackerInfoRepository())->info();
    }
}

?>