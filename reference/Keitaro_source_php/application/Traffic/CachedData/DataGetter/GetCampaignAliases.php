<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetCampaignAliases implements DataGetterInterface
{
    const NAME = "campaign_aliases";
    const CACHE_KEY = "CMPALIASES";
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
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        $result = \Component\Campaigns\Repository\CampaignRepository::instance()->rawRows("id, alias", $where);
        $hash = [];
        foreach ($result as $row) {
            $hash[$row["alias"]] = $row["id"];
        }
        return $hash;
    }
}

?>