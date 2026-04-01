<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\ProIP;

class ProIPDBEssential extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "proip_essential";
    const SETTING_KEY = "proip_essential_key";
    const DATABASE_FILENAME = "PROIP-ESSENTIAL.DAT";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "ProIP Essential", "type" => \Component\GeoDb\GeoDbType::EXTERNAL, "data_types" => [\Traffic\GeoDb\IpInfoType::COUNTRY, \Traffic\GeoDb\IpInfoType::REGION, \Traffic\GeoDb\IpInfoType::CITY, \Traffic\GeoDb\IpInfoType::ISP], "file_path" => ROOT . "/var/geoip/ProIP/Essential/PROIP-ESSENTIAL.DAT", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/ip2location", "is_recommended" => true, "setting_key" => SETTING_KEY, "purchase_link" => "https://keitaro.io/go/proip"]));
        $this->setManager(new ProIPDownloadManager($this->definition(), [ProIPDownloadManager::TOKEN => \Traffic\Repository\CachedSettingsRepository::instance()->get(SETTING_KEY), "databaseFileName" => DATABASE_FILENAME]));
        $this->setAdapter(new ProIPAdapter($this->definition()));
    }
}

?>