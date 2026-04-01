<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Maxmind;

class MaxmindIsp extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "maxmind_isp";
    const SETTING_KEY = "maxmind_isp_key";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "Maxmind ISP", "type" => \Component\GeoDb\GeoDbType::EXTERNAL, "data_types" => [\Traffic\GeoDb\IpInfoType::ISP], "file_path" => ROOT . "/var/geoip/GeoISP/GeoISP.dat", "dictionaries_path" => ROOT . "/application/Component/GeoDb/dictionaries/maxmind", "setting_key" => SETTING_KEY]));
        $this->setManager(new MaxmindDownloadManager($this->definition(), [MaxmindDownloadManager::KEY => \Traffic\Repository\CachedSettingsRepository::instance()->get(SETTING_KEY), MaxmindDownloadManager::EDITION => 121]));
        $this->setAdapter(new MaxmindIspAdapter($this->definition()));
    }
}

?>