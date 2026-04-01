<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Model;

class UserBotIp extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "user_bot_ips";
    const MIN_IP_POS = 0;
    const MAX_IP_POS = 1;
    const RAW_VAL_POS = 2;
    public function getRaw()
    {
        $result = [$this->getMinIp(), $this->getMaxIp(), $this->getRawValue()];
        return $result;
    }
    public function getMinIp()
    {
        return $this->get("min_ip");
    }
    public function getMaxIp()
    {
        return $this->get("max_ip");
    }
    public function getRawValue()
    {
        return $this->get("raw_value");
    }
}

?>