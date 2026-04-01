<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoProfiles\Service;

class GeoProfileService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\GeoProfiles\Model\GeoProfile::definition();
    }
    public function parseCountriesAndCreate($params)
    {
        if (is_array($params["countries"])) {
            $params["countries"] = implode(" ", $params["countries"]);
        }
        return $this->create($params);
    }
    public function parseCountriesAndUpdate($entity, $params)
    {
        if (is_array($params["countries"])) {
            $params["countries"] = implode(" ", $params["countries"]);
        }
        return $this->update($entity, $params);
    }
}

?>