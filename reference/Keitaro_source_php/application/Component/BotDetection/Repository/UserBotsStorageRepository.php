<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Repository;

class UserBotsStorageRepository extends \Traffic\Service\AbstractService
{
    private $_redisStorage = NULL;
    private $_dbcaStorage = NULL;
    private $_forceLegacy = false;
    public function forceLegacy()
    {
        $this->_forceLegacy = true;
    }
    public function getMainStorage()
    {
        if (empty($this->_dbcaStorage)) {
            $this->_dbcaStorage = new \Component\BotDetection\BotsStorage\DBCAStorage();
        }
        return $this->_dbcaStorage;
    }
    public function getCurrentStorage()
    {
        return $this->getMainStorage();
    }
    public function isMigratedToNewFormat()
    {
        $path = UserBotDBCARepository::instance()->getCurrentDbPath();
        $legacyPath = UserBotsLegacyRepository::instance()->getFullPath();
        return is_file($path) || !is_file($legacyPath);
    }
    public function getRepository()
    {
        if ($this->isMigratedToNewFormat() && !$this->_forceLegacy) {
            return UserBotsRepository::instance();
        }
        return UserBotsLegacyRepository::instance();
    }
    public function getService()
    {
        if ($this->isMigratedToNewFormat() && !$this->_forceLegacy) {
            return \Component\BotDetection\Service\UserBotsService::instance();
        }
        return \Component\BotDetection\Service\UserBotsLegacyService::instance();
    }
}

?>