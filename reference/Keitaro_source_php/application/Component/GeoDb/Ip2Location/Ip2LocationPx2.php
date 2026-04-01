<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Ip2Location;

class Ip2LocationPx2 extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "ip2location_px2";
    const SETTING_KEY = "ip2location_px2_token";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "IP2Location PX2", "type" => \Component\GeoDb\GeoDbType::EXTERNAL, "data_types" => [\Traffic\GeoDb\IpInfoType::COUNTRY, \Traffic\GeoDb\IpInfoType::PROXY_TYPE], "file_path" => ROOT . "/var/geoip/IP2Location/PX2/IP2PROXY-IP-PROXYTYPE-COUNTRY.BIN", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/ip2location", "is_recommended" => true, "setting_key" => SETTING_KEY, "purchase_link" => "https://keitaro.io/go/iptolocation"]));
        $this->setManager(new Ip2LocationDownloadManager($this->definition(), [Ip2LocationDownloadManager::TOKEN => \Traffic\Repository\CachedSettingsRepository::instance()->get(SETTING_KEY), Ip2LocationDownloadManager::DB_CODE => "PX2BIN"]));
        $this->setAdapter(new Ip2LocationAdapter($this->definition()));
    }
}

?>