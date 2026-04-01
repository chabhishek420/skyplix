<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetStreamFilters implements DataGetterInterface
{
    const NAME = "stream_filters";
    const CACHE_KEY = "FLTRS";
    public function name()
    {
        return NAME;
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        if (empty($scope["stream_id"])) {
            throw new \Exception("No 'stream_id'");
        }
        $key = self::cacheKey($scope["stream_id"]);
        $result = $storage->get($key);
        $filters = [];
        foreach ($result as $rawData) {
            $filters[] = \Component\StreamFilters\Service\StreamFilterService::instance()->restore($rawData);
        }
        return $filters;
    }
    public function fallback($scope = NULL)
    {
        return \Component\StreamFilters\Repository\StreamFilterRepository::instance()->all("stream_id = " . (int) $scope["stream_id"], "id");
    }
    public static function cacheKey($streamId)
    {
        return implode("_", [CACHE_KEY, $streamId]);
    }
}

?>