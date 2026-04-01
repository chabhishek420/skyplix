<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\BotsStorage;

class MysqlStorage implements StorageInterface
{
    public function getList()
    {
        $ips = \Component\BotDetection\Repository\UserBotIpRepository::instance()->all();
        $result = [];
        foreach ($ips as $ip) {
            $result[] = $ip->getRaw();
        }
        return $result;
    }
    public function saveList($list)
    {
        \Core\Db\Db::instance()->beginTransaction();
        \Component\BotDetection\Service\UserBotIpService::instance()->directDeleteAll(1);
        foreach ($list as $rawUserIp) {
            \Component\BotDetection\Service\UserBotIpService::instance()->create(["min_ip" => $rawUserIp[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS], "max_ip" => $rawUserIp[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS], "raw_value" => $rawUserIp[\Component\BotDetection\Model\UserBotIp::RAW_VAL_POS]]);
        }
        \Core\Db\Db::instance()->commit();
    }
    public function clear()
    {
        \Component\BotDetection\Service\UserBotIpService::instance()->directDeleteAll(1);
    }
    public function itemExists($ip)
    {
        $intIp = ip2long($ip);
        $result = \Component\BotDetection\Repository\UserBotIpRepository::instance()->findFirst("min_ip <= " . $intIp . " AND " . $intIp . " <= max_ip");
        return !empty($result);
    }
}

?>