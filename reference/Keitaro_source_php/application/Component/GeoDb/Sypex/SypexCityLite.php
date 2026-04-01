<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Sypex;

class SypexCityLite extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "sypex_lite";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "Sypex City Lite", "type" => \Component\GeoDb\GeoDbType::HOSTED, "data_types" => [\Traffic\GeoDb\IpInfoType::COUNTRY, \Traffic\GeoDb\IpInfoType::REGION, \Traffic\GeoDb\IpInfoType::CITY, \Traffic\GeoDb\IpInfoType::CITY_RU], "file_path" => ROOT . "/var/geoip/SxGeoCity.dat", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/sypex"]));
        $this->setAdapter(new SypexCityAdapter($this->definition()));
        $this->setManager(new \Component\GeoDb\DownloadManager\HostedDownloadManager($this->definition(), [\Component\GeoDb\DownloadManager\HostedDownloadManager::CRC_URL => "https://keitaro.io/db/SxGeoCity.crc", \Component\GeoDb\DownloadManager\HostedDownloadManager::DB_URL => "https://keitaro.io/db/SxGeoCity.gz"]));
    }
}

?>