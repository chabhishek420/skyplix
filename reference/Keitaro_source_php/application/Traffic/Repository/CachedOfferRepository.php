<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class CachedOfferRepository extends AbstractBaseRepository
{
    public function findCached($id)
    {
        return \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\Model\Offer::entityName(), $id);
    }
}

?>