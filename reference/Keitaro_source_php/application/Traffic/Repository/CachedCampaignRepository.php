<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class CachedCampaignRepository extends AbstractBaseRepository
{
    public function findByToken($token)
    {
        $hash = \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\CachedData\DataGetter\GetCampaignTokens::NAME);
        if (isset($hash[$token])) {
            return $this->findInCacheById($hash[$token]);
        }
        return NULL;
    }
    public function findByAlias($alias)
    {
        $hash = \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\CachedData\DataGetter\GetCampaignAliases::NAME);
        if (isset($hash[$alias])) {
            return $this->findInCacheById($hash[$alias]);
        }
        return NULL;
    }
    public function findInCacheById($id)
    {
        return \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\CachedData\DataGetter\GetCampaign::NAME, $id);
    }
}

?>