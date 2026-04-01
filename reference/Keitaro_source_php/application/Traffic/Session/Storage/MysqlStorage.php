<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Session\Storage;

class MysqlStorage implements StorageInterface
{
    private $_memoized = [];
    const TABLE_NAME = "sessions";
    public function save($save, $uniquenessId, \Traffic\Session\SessionEntry $entry, string $ttlInSec)
    {
        $payload = json_encode($entry->getData());
        $date = new \DateTime("+" . $ttlInSec . "seconds");
        $expiresAt = $date->format(\Core\Model\AbstractModel::DATETIME_FORMAT);
        try {
            \Core\Db\Db::instance()->insert(\Traffic\Session\Session::getTableName(), ["visitor_code" => $uniquenessId, "expires_at" => $expiresAt, "payload" => $payload], "ON DUPLICATE KEY UPDATE expires_at = " . \Core\Db\Db::quote($expiresAt) . ", payload = " . \Core\Db\Db::quote($payload));
        } catch (\ADODB_Exception $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error("Error connecting to MySQL. Skip saving uniqueness state. Message: " . $e->getMessage());
        }
    }
    public function getSessionEntry(\Traffic\Session\SessionEntry $getSessionEntry, $uniquenessId)
    {
        if (empty($this->_memoized[$uniquenessId])) {
            $data = $this->_loadFromDb($uniquenessId);
            $sessionEntry = \Traffic\Session\SessionEntry::restore($data);
            $this->_memoized[$uniquenessId] = $sessionEntry;
        }
        return $this->_memoized[$uniquenessId];
    }
    public function prune()
    {
        $date = new \DateTime();
        $sql = "DELETE FROM " . $this->_getTableName() . " \n          WHERE expires_at < " . \Core\Db\Db::quote($date->format(\Core\Model\AbstractModel::DATETIME_FORMAT));
        \Core\Db\Db::instance()->execute($sql);
    }
    public function flushMemo()
    {
        $this->_memoized = [];
    }
    private function _loadFromDb($uniquenessId)
    {
        $sql = "SELECT * FROM " . $this->_getTableName() . " WHERE visitor_code = " . \Core\Db\Db::quote($uniquenessId);
        try {
            $row = \Core\Db\Db::instance()->getRow($sql);
        } catch (\ADODB_Exception $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error("DB is down. Skip loading uniqueness state");
            if (!empty($row)) {
                return json_decode($row["payload"], true);
            }
            return NULL;
        }
    }
    private function _getTableName()
    {
        return \Core\Db\Db::getPrefix() . TABLE_NAME;
    }
}

?>