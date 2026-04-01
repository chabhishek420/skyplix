<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Keitaro;

class KeitaroBotDb2 extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "keitaro_bot_db2";
    const GENERIC_BOT = "generic_bot";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "name" => "Keitaro BotDB2", "type" => \Component\GeoDb\GeoDbType::HOSTED, "data_types" => [\Traffic\GeoDb\IpInfoType::BOT_TYPE], "file_path" => ROOT . "/var/bots/botsV2.dat", "is_recommended" => true]));
        $this->setAdapter(new KeitaroBotDb2Adapter($this->definition()));
        $key = \Core\Application\LicenseService::instance()->getKey();
        $ip = \Core\Application\LicenseService::instance()->getLicenseIp();
        $this->setManager(new \Component\GeoDb\DownloadManager\HostedDownloadManager($this->definition(), [\Component\GeoDb\DownloadManager\HostedDownloadManager::CRC_URL => "https://keitaro.io/files/botsdb?ip=" . $ip . "&key=" . $key . "&extension=crc", \Component\GeoDb\DownloadManager\HostedDownloadManager::DB_URL => "https://keitaro.io/files/botsdb?ip=" . $ip . "&key=" . $key . "&extension=gz"]));
    }
}

?>