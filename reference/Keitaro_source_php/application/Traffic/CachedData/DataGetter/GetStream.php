<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetStream implements DataGetterInterface
{
    const NAME = "get_stream";
    const CACHE_KEY = "STRM";
    public function name()
    {
        return NAME;
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        if (!is_numeric($scope)) {
            throw new \Exception("\$scope must be an ID");
        }
        $rawData = $storage->get(self::cacheKey($scope));
        return \Component\Streams\Service\StreamService::instance()->restore($rawData);
    }
    public function fallback($scope = NULL)
    {
        return \Component\Streams\Repository\StreamRepository::instance()->find($scope);
    }
    public static function cacheKey($id)
    {
        return implode("_", [CACHE_KEY, $id]);
    }
}

?>