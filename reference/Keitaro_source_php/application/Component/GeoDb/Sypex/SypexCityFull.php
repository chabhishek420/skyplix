<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Sypex;

class SypexCityFull extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "sypex_full";
    const SETTING_KEY = "sx_full_key";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "Sypex City Full", "type" => \Component\GeoDb\GeoDbType::EXTERNAL, "data_types" => [\Traffic\GeoDb\IpInfoType::COUNTRY, \Traffic\GeoDb\IpInfoType::REGION, \Traffic\GeoDb\IpInfoType::CITY, \Traffic\GeoDb\IpInfoType::CITY_RU], "file_path" => ROOT . "/var/geoip/SxGeoCity/SxGeoCity.dat", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/sypex", "setting_key" => SETTING_KEY]));
        $this->setManager(new SypexDownloadManager($this->definition(), [SypexDownloadManager::KEY => \Traffic\Repository\CachedSettingsRepository::instance()->get(SETTING_KEY), SypexDownloadManager::DB_FILE => "SxGeoCity.zip", SypexDownloadManager::UNPACKED_DB_FILE => "SxGeoCity.dat"]));
        $this->setAdapter(new SypexCityAdapter($this->definition()));
    }
}

?>