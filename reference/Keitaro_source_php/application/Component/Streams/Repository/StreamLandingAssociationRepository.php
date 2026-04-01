<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Repository;

class StreamLandingAssociationRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\Model\StreamLandingAssociation::definition();
    }
    public function allByStream(\Traffic\Model\BaseStream $stream)
    {
        return $this->all("stream_id = " . \Core\Db\Db::quote($stream->getId()), "id");
    }
    public function allByLanding(\Traffic\Model\Landing $landing)
    {
        return $this->all("landing_id = " . \Core\Db\Db::quote($landing->getId()), "id");
    }
}

?>