<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class CachedStreamRepository extends AbstractBaseRepository
{
    public function getCachedActiveStreams(\Traffic\Model\Campaign $campaign)
    {
        $result = \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\CachedData\DataGetter\GetStreams::NAME, ["campaign_id" => $campaign->getId()]);
        return new \Traffic\Model\StreamCollection($result);
    }
    public function findCachedStream($id)
    {
        return \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\CachedData\DataGetter\GetStream::NAME, $id);
    }
}

?>