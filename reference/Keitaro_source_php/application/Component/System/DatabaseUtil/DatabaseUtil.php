<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\DatabaseUtil;

class DatabaseUtil
{
    private $_tables = NULL;
    public function __construct()
    {
        $this->_tables = $this->_loadTablesList();
    }
    public function getTables()
    {
        return array_keys($this->_tables);
    }
    public function optimizeTable($tableName)
    {
        if (!$this->_isTableExists($tableName)) {
            return false;
        }
        $name = $this->_tables[$tableName]["TABLE_NAME"];
        $sql = "OPTIMIZE TABLE " . $name;
        return \Core\Db\Db::instance()->execute($sql);
    }
    public function truncateTable($tableName)
    {
        if (!$this->_isTableExists($tableName)) {
            return false;
        }
        $name = $this->_tables[$tableName]["TABLE_NAME"];
        $sql = "TRUNCATE table " . $name;
        return \Core\Db\Db::instance()->execute($sql);
    }
    private function _loadTablesList()
    {
        $tables = [];
        $name = \Traffic\Service\ConfigService::instance()->get("db", "name");
        $sql = "SELECT * FROM `information_schema`.`tables`\n          WHERE `table_schema` = '" . $name . "'\n          ORDER BY `table_name`";
        $rows = \Core\Db\Db::instance()->getAll($sql);
        if ($rows && is_array($rows) && 0 < count($rows)) {
            foreach ($rows as $r) {
                $tables[strtolower($r["TABLE_NAME"])] = $r;
            }
        }
        return $tables;
    }
    private function _isTableExists($tableName)
    {
        $tableName = strtolower($tableName);
        if (!array_key_exists($tableName, $this->_tables)) {
            return false;
        }
        return true;
    }
}

?>