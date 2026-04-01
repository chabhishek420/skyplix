<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Ip2Location;

class Ip2LocationDb4 extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "ip2location_full_isp";
    const SETTING_KEY = "ip2location_full_isp_token";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "IP2Location DB4", "type" => \Component\GeoDb\GeoDbType::EXTERNAL, "data_types" => [\Traffic\GeoDb\IpInfoType::COUNTRY, \Traffic\GeoDb\IpInfoType::REGION, \Traffic\GeoDb\IpInfoType::CITY, \Traffic\GeoDb\IpInfoType::ISP], "file_path" => ROOT . "/var/geoip/IP2Location/full_isp/IPV6-COUNTRY-REGION-CITY-ISP.BIN", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/ip2location", "is_recommended" => true, "setting_key" => SETTING_KEY, "purchase_link" => "https://keitaro.io/go/iptolocation"]));
        $this->setManager(new Ip2LocationDownloadManager($this->definition(), [Ip2LocationDownloadManager::TOKEN => \Traffic\Repository\CachedSettingsRepository::instance()->get(SETTING_KEY), Ip2LocationDownloadManager::DB_CODE => "DB4BINIPV6"]));
        $this->setAdapter(new Ip2LocationAdapter($this->definition()));
    }
}

?>