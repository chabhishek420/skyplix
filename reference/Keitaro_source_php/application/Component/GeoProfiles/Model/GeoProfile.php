<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoProfiles\Model;

class GeoProfile extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "country_profiles";
    public static function service()
    {
        return \Component\GeoProfiles\Service\GeoProfileService::instance();
    }
    public function getName()
    {
        return $this->get("name");
    }
    public function getCountries()
    {
        return $this->get("countries");
    }
}

?>