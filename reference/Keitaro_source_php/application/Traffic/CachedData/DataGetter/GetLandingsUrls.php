<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetLandingsUrls implements DataGetterInterface
{
    const NAME = "landing_urls";
    const CACHE_KEY = "L_URLS";
    public function name()
    {
        return NAME;
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        return $storage->get(CACHE_KEY);
    }
    public function fallback($scope = NULL)
    {
        return self::buildHash();
    }
    public static function buildHash()
    {
        $landings = \Component\Landings\Repository\LandingRepository::instance()->rawRows("id, action_payload", "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED));
        $urls = [];
        foreach ($landings as $landing) {
            $urls[$landing["id"]] = $landing["action_payload"];
        }
        return $urls;
    }
}

?>