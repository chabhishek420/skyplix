<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class StreamOfferAssociation extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "stream_offer_associations";
    protected static $_entityName = "offer_stream_association";
    public static function validator()
    {
        return new \Component\Offers\Validator\StreamOfferAssociationValidator();
    }
    public static function repository()
    {
        return \Component\Streams\Repository\StreamOfferAssociationRepository::instance();
    }
    public static function service()
    {
        return \Component\Offers\Service\StreamOfferAssociationService::instance();
    }
    public function getShare()
    {
        return $this->get("share");
    }
    public function getState()
    {
        return $this->get("state");
    }
    public function getOfferId()
    {
        return $this->get("offer_id");
    }
}

?>