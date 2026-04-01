<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoProfiles\Serializer;

class DecoratedGeoProfileSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    public function extra($model, $data)
    {
        $countries = $model->getCountries();
        if (!empty($countries) && !is_array($countries)) {
            $countries = explode(" ", $countries);
        }
        $data["countries"] = $countries;
        $data["decorated_countries"] = implode(", ", \Component\GeoDb\Repository\CountriesRepository::instance()->getNames($countries));
        return $data;
    }
}

?>