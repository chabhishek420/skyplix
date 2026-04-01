<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Keitaro;

class KeitaroBotDb2Adapter implements \Component\GeoDb\Adapter\GeoDbAdapterInterface
{
    private $_client = NULL;
    const BOT_VALUE = 1;
    const BLACKLIST_VALUE = 2;
    const SPAMBOT = 3;
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition)
    {
        $this->_client = new DbcaClient($definition);
    }
    public function info($ip)
    {
        $code = $this->_client->rawInfo($ip);
        if (empty($code)) {
            return [\Traffic\GeoDb\IpInfoType::BOT_TYPE => false];
        }
        $code = explode(",", $code);
        if (isset($code[0]) && $code[0] == SPAMBOT && !\Traffic\Repository\CachedSettingsRepository::instance()->get("detect_spam_bots", 0)) {
            return [\Traffic\GeoDb\IpInfoType::BOT_TYPE => NULL];
        }
        return [\Traffic\GeoDb\IpInfoType::BOT_TYPE => KeitaroBotDb2::GENERIC_BOT];
    }
    public function rawInfo($ip)
    {
        return $this->_client->rawInfo($ip);
    }
    public function getDbInfo()
    {
        return $this->_client->getDbInfo();
    }
}

?>