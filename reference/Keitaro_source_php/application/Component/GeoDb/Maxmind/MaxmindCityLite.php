<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Maxmind;

class MaxmindCityLite extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "maxmind_lite";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "Maxmind City Lite (Legacy)", "type" => \Component\GeoDb\GeoDbType::HOSTED, "data_types" => [\Traffic\GeoDb\IpInfoType::COUNTRY, \Traffic\GeoDb\IpInfoType::REGION, \Traffic\GeoDb\IpInfoType::CITY], "file_path" => ROOT . "/var/geoip/GeoLiteCity.dat", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/maxmind"]));
        $this->setAdapter(new MaxmindCityAdapter($this->definition()));
        $this->setManager(new \Component\GeoDb\DownloadManager\HostedDownloadManager($this->definition(), [\Component\GeoDb\DownloadManager\HostedDownloadManager::CRC_URL => "https://keitaro.io/db/GeoLiteCity.dat.crc", \Component\GeoDb\DownloadManager\HostedDownloadManager::DB_URL => "https://keitaro.io/db/GeoLiteCity.dat.gz"]));
    }
}

?>