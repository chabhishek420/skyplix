<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class CachedStreamFilterRepository extends AbstractBaseRepository
{
    public function allCached(\Traffic\Model\BaseStream $stream)
    {
        return \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\CachedData\DataGetter\GetStreamFilters::NAME, ["stream_id" => $stream->getId()]);
    }
}

?>