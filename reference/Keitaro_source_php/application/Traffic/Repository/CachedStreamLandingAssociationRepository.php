<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class CachedStreamLandingAssociationRepository extends AbstractBaseRepository
{
    public function getCachedByStream(\Traffic\Model\BaseStream $stream)
    {
        return \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\Model\StreamLandingAssociation::entityName(), ["stream_id" => $stream->getId()]);
    }
}

?>