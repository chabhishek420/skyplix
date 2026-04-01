<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Service;

class TokuDbService extends \Traffic\Service\AbstractService
{
    const TOKU_DB = "TokuDB";
    const ENGINE_FIELD = "Engine";
    private function _isAvailable()
    {
        try {
            $result = \Core\Db\Db::instance()->getOne("SELECT Engine FROM `information_schema`.`ENGINES` WHERE Engine = 'TokuDB'");
            return !empty($result);
        } catch (\ADODB_Exception $e) {
            return false;
        }
    }
    private function _getTables()
    {
        $prefix = \Core\Db\Db::instance()->getPrefix();
        $dbName = \Core\Db\Db::quote(\Traffic\Service\ConfigService::instance()->get("db", "name"));
        $sql = "SELECT TABLE_NAME \n        FROM INFORMATION_SCHEMA.TABLES\n        WHERE TABLE_SCHEMA = " . $dbName . "\n            AND TABLE_NAME LIKE '" . $prefix . "%'";
        $rows = \Core\Db\Db::instance()->fetchRows($sql);
        $tables = [];
        foreach ($rows as $row) {
            $tables[] = $row["TABLE_NAME"];
        }
        return $tables;
    }
    public function updateEngine()
    {
        $tables = $this->_getTables();
        foreach ($tables as $table) {
            if (!$this->isTableConverted($table)) {
                \Core\Db\Db::instance()->execute("ALTER TABLE `" . $table . "` ENGINE=TokuDB");
            }
        }
    }
    public function isTableConverted($tableName)
    {
        $sql = "SHOW TABLE STATUS WHERE Name = " . \Core\Db\Db::quote($tableName);
        $rows = \Core\Db\Db::instance()->fetchRows($sql);
        if (empty($rows)) {
            return false;
        }
        return $rows[0][ENGINE_FIELD] == TOKU_DB;
    }
    public function isClicksTableInTokuDB()
    {
        $tableName = \Traffic\Model\Click::getTableName();
        return TokuDbService::instance()->isTableConverted($tableName);
    }
    public function isConverted()
    {
        $tables = $this->_getTables();
        $sql = "SHOW TABLE STATUS WHERE Name in (" . implode(",", \Core\Db\Db::quote($tables)) . ")";
        $rows = \Core\Db\Db::instance()->fetchRows($sql);
        foreach ($rows as $row) {
            if ($row[ENGINE_FIELD] != TOKU_DB) {
                return false;
            }
        }
        return true;
    }
    public function getStatus()
    {
        if (!$this->_isAvailable()) {
            return "unavailable";
        }
        if ($this->isConverted()) {
            return "yes";
        }
        return "no";
    }
}

?>