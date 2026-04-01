<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetCampaign implements DataGetterInterface
{
    const NAME = "campaign";
    const CACHE_KEY = "CMPGN";
    public function name()
    {
        return NAME;
    }
    public static function cacheKey($id)
    {
        return CACHE_KEY . "_" . $id;
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        if (!is_numeric($scope)) {
            throw new \Exception("ID must be a number");
        }
        $result = $storage->get(self::cacheKey($scope));
        if ($result === false) {
            return $result;
        }
        return \Component\Campaigns\Service\CampaignService::instance()->restore($result);
    }
    public function fallback($scope = NULL)
    {
    }
}

?>