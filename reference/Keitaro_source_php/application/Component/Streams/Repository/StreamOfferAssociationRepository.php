<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Repository;

class StreamOfferAssociationRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\Model\StreamOfferAssociation::definition();
    }
    public function allByStream(\Traffic\Model\BaseStream $stream)
    {
        return $this->all("stream_id = " . \Core\Db\Db::quote($stream->getId()), "id");
    }
    public function allByOffer(\Traffic\Model\Offer $offer)
    {
        return $this->all("offer_id = " . \Core\Db\Db::quote($offer->getId()), "id");
    }
}

?>