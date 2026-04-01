<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Keitaro;

class KeitaroCarrierDb extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "keitaro_carrier";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "Keitaro Mobile Operator v3", "type" => \Component\GeoDb\GeoDbType::HOSTED, "data_types" => [\Traffic\GeoDb\IpInfoType::OPERATOR, \Traffic\GeoDb\IpInfoType::CONNECTION_TYPE], "file_path" => ROOT . "/var/geoip/carriers.dat", "is_recommended" => true]));
        $this->setAdapter(new KeitaroCarrierDbAdapter($this->definition()));
        $ip = \Core\Application\LicenseService::instance()->getLicenseIp();
        $key = \Core\Application\LicenseService::instance()->getKey();
        $this->setManager(new \Component\GeoDb\DownloadManager\HostedDownloadManager($this->definition(), [\Component\GeoDb\DownloadManager\HostedDownloadManager::CRC_URL => "https://keitaro.io/files/carrierdb?ip=" . $ip . "&key=" . $key . "&extension=crc", \Component\GeoDb\DownloadManager\HostedDownloadManager::DB_URL => "https://keitaro.io/files/carrierdb?ip=" . $ip . "&key=" . $key . "&extension=gz"]));
    }
}

?>