<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Sypex;

class SypexCityMax extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "sypex_max";
    const SETTING_KEY = "sx_max_key";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "Sypex City Max", "type" => \Component\GeoDb\GeoDbType::EXTERNAL, "data_types" => [\Traffic\GeoDb\IpInfoType::COUNTRY, \Traffic\GeoDb\IpInfoType::REGION, \Traffic\GeoDb\IpInfoType::CITY, \Traffic\GeoDb\IpInfoType::CITY_RU], "file_path" => ROOT . "/var/geoip/SxGeoMax/SxGeoMax.dat", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/sypex", "setting_key" => SETTING_KEY]));
        $this->setManager(new SypexDownloadManager($this->definition(), [SypexDownloadManager::KEY => \Traffic\Repository\CachedSettingsRepository::instance()->get(SETTING_KEY), SypexDownloadManager::DB_FILE => "SxGeoMax.zip", SypexDownloadManager::UNPACKED_DB_FILE => "SxGeoMax.dat"]));
        $this->setAdapter(new SypexCityAdapter($this->definition()));
    }
}

?>