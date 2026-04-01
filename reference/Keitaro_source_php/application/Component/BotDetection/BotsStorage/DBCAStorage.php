<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\BotsStorage;

class DBCAStorage implements StorageInterface
{
    const LOCK_NAME = "bots_dbca";
    public function getList()
    {
        $result = [];
        $version = \Component\BotDetection\Repository\UserBotDBCARepository::instance()->getCurrentVersion();
        $dbStream = \Component\BotDetection\Repository\UserBotDBCARepository::instance()->getDbStream(true, $version);
        if (empty($dbStream)) {
            return $result;
        }
        $dbReader = new \Core\KeitaroDb\DBCA\DbReader($dbStream);
        $list = $dbReader->getFullList();
        $dbStream->close();
        return $list;
    }
    public function saveList($list)
    {
        $handle = \Core\Lock\LockService::instance()->waitForLock(LOCK_NAME);
        if (empty($handle)) {
            throw new \Core\Application\Exception\Error("Creating a new Bot DB is in progress.");
        }
        $currentVersion = \Component\BotDetection\Repository\UserBotDBCARepository::instance()->getCurrentVersion();
        $newVersion = $currentVersion + 1;
        $dbDataProcessor = new \Core\KeitaroDb\DBCA\DbDataProcessor();
        foreach ($list as $rawUserIp) {
            $ipSeg = new \Core\KeitaroDb\Common\IpSegment($rawUserIp[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS], $rawUserIp[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS]);
            $dbDataProcessor->addInterval($ipSeg, $rawUserIp[\Component\BotDetection\Model\UserBotIp::RAW_VAL_POS]);
        }
        $dbDataProcessor->sort();
        $dbStream = \Component\BotDetection\Repository\UserBotDBCARepository::instance()->getDbStream(false, $newVersion);
        $dbWriter = new \Core\KeitaroDb\DBCA\DbWriter($newVersion, $dbDataProcessor, $dbStream);
        $dbWriter->writeDb();
        $dbStream->close();
        unset($dbStream);
        unset($dbWriter);
        $this->_setVersion($newVersion);
        $this->_reloadDbAdapter();
        \Core\Lock\LockService::instance()->unlock($handle, LOCK_NAME);
    }
    private function _setVersion($version)
    {
        file_put_contents(\Component\BotDetection\Repository\UserBotDBCARepository::instance()->getVersionPath(), $version);
    }
    public function clear()
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[DBCAStorage] clean");
        $currentVersion = \Component\BotDetection\Repository\UserBotDBCARepository::instance()->getCurrentVersion();
        $newVersion = $currentVersion + 1;
        $this->_setVersion($newVersion);
        $this->_reloadDbAdapter();
    }
    public function itemExists($ip)
    {
        $db = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->getDb(\Component\GeoDb\Keitaro\InternalUserBotDb::ID);
        if ($db && $db->exists()) {
            $info = \Traffic\GeoDb\Service\GeoDbService::instance()->rawInfo($db, $ip);
            return !empty($info);
        }
        return !empty($info);
    }
    private function _reloadDbAdapter()
    {
        \Traffic\GeoDb\Repository\GeoDbRepository::instance()->addDb(new \Component\GeoDb\Keitaro\InternalUserBotDb());
    }
}

?>