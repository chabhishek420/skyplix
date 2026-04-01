<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Repository;

class UserBotsRepository extends \Traffic\Service\AbstractService implements UserBotsRepositoryInterface
{
    public function getListCount()
    {
        $list = UserBotsStorageRepository::instance()->getMainStorage()->getList();
        return count($list);
    }
    public function getList()
    {
        $list = UserBotsStorageRepository::instance()->getMainStorage()->getList();
        $result = [];
        foreach ($list as $pod) {
            $result[] = $pod[\Component\BotDetection\Model\UserBotIp::RAW_VAL_POS];
        }
        return implode("\n", $result);
    }
    public function exists($ip)
    {
        $storage = UserBotsStorageRepository::instance()->getCurrentStorage();
        return $storage->itemExists($ip);
    }
}

?>