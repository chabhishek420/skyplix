<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class StreamLandingAssociation extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "stream_landing_associations";
    protected static $_entityName = "landing_stream_association";
    public static function validator()
    {
        return new \Component\Landings\Validator\StreamLandingAssociationValidator();
    }
    public static function repository()
    {
        return \Component\Streams\Repository\StreamLandingAssociationRepository::instance();
    }
    public static function service()
    {
        return \Component\Landings\Service\StreamLandingAssociationService::instance();
    }
    public function getShare()
    {
        return $this->get("share");
    }
    public function getState()
    {
        return $this->get("state");
    }
    public function getLandingId()
    {
        return $this->get("landing_id");
    }
}

?>