<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Keitaro;

class InternalUserBotDb extends \Component\GeoDb\AbstractGeoDb
{
    const ID = "user_bot_ip_db";
    public function __construct()
    {
        $this->setDefinition(new \Component\GeoDb\GeoDbDefinition(["id" => ID, "type" => \Component\GeoDb\GeoDbType::INTERNAL, "data_types" => [\Traffic\GeoDb\IpInfoType::BOT_TYPE], "file_path" => function () {
            $version = \Component\BotDetection\Repository\UserBotDBCARepository::instance()->getCurrentVersion();
            return \Component\BotDetection\Repository\UserBotDBCARepository::instance()->getFullPath($version);
        }]));
        $this->setAdapter(new KeitaroBotDb2Adapter($this->definition()));
        $this->setManager(new \Component\GeoDb\DownloadManager\NullDownloadManager($this->definition()));
    }
}

?>