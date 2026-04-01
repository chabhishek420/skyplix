<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Ip2Location;

class Ip2LocationDb3Lite extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "ip2location_lite";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "IP2Location DB3 Lite", "type" => \Component\GeoDb\GeoDbType::HOSTED, "data_types" => [\Traffic\GeoDb\IpInfoType::COUNTRY, \Traffic\GeoDb\IpInfoType::REGION, \Traffic\GeoDb\IpInfoType::CITY], "file_path" => ROOT . "/var/geoip/IP2Location/lite/IP2LOCATION-LITE-DB3.BIN", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/ip2location"]));
        $this->setManager(new \Component\GeoDb\DownloadManager\HostedDownloadManager($this->definition(), [\Component\GeoDb\DownloadManager\HostedDownloadManager::CRC_URL => "https://keitaro.io/geoip/IP2LOCATION-LITE-DB3.BIN.crc", \Component\GeoDb\DownloadManager\HostedDownloadManager::DB_URL => "https://keitaro.io/geoip/IP2LOCATION-LITE-DB3.BIN.gz"]));
        $this->setAdapter(new Ip2LocationAdapter($this->definition()));
    }
}

?>