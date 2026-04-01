<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

class Migration
{
    const DESCRIPTION = "empty";
    public function run($rerun = false)
    {
        return self::up($rerun);
    }
    public function getDescription($lang)
    {
        return constant(get_called_class() . "::DESCRIPTION_" . strtoupper($lang));
    }
    public function getName()
    {
        return get_called_class();
    }
    public function getVersion()
    {
        return (int) str_replace("Migration_", "", get_called_class());
    }
    public static function getDb()
    {
        return Core\Db\Db::instance();
    }
    public static function getPrefix()
    {
        return self::getDb()->getPrefix();
    }
    public static function getFieldsDic($tableName)
    {
        $prefix = self::getPrefix();
        $sql = "describe " . $prefix . $tableName;
        $fields = [];
        $result = self::getDb()->execute($sql);
        foreach ($result as $res) {
            $fields[$res["Field"]] = 1;
        }
        return $fields;
    }
    public static function addColumnsIfNotExist($tableName, $columnsDesc, $withIndex = false)
    {
        $prefix = self::getPrefix();
        $fields = self::getFieldsDic($tableName);
        $sqls = [];
        $indexSql = [];
        foreach ($columnsDesc as $column => $desc) {
            if (!isset($fields[$column])) {
                $sqls[] = "ADD COLUMN `" . $column . "` " . $desc;
                $indexSql[] = "ADD INDEX (" . $column . ")";
            }
        }
        if (count($sqls)) {
            $sql = "ALTER TABLE " . $prefix . $tableName . " " . implode(",", $sqls);
            if ($withIndex) {
                $sql .= ", " . implode(",", $indexSql);
            }
            self::execute($sql);
        }
    }
    public static function getSetting($key)
    {
        $prefix = self::getPrefix();
        $sql = "select value\n        from " . $prefix . "settings\n        where `key` = " . Core\Db\Db::quote($key);
        $value = NULL;
        $result = self::getDb()->execute($sql);
        foreach ($result as $res) {
            $value = $res["value"];
        }
        return $value;
    }
    public static function addToConfig($rows, $addSpace = true)
    {
        $text = implode("\n", $rows);
        if ($addSpace) {
            $text = "\n\n" . $text;
        }
        file_put_contents(ROOT . "/application/config/config.ini.php", $text, FILE_APPEND);
    }
    public static function execute(ADORecordSet_pdo $execute, $sql)
    {
        return self::getDb()->execute($sql);
    }
    public static function silentExecute($sql)
    {
        try {
            return self::execute($sql);
        } catch (ADODB_Exception $e) {
            Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
        }
    }
    public static function executeIgnore($sql, $match = "Duplicate column")
    {
        try {
            self::execute($sql);
        } catch (ADODB_Exception $e) {
            if (!stristr($e->getMessage(), $match)) {
                throw $e;
            }
        }
    }
    public static function moveUserBotListV8ToV9()
    {
        $list = Component\BotDetection\Repository\UserBotsLegacyRepository::instance()->getList();
        if (!empty($list)) {
            Component\BotDetection\Service\UserBotsService::instance()->saveList($list);
        }
        Component\BotDetection\Repository\UserBotsLegacyRepository::instance()->clearList();
    }
    public static function warmUpCache()
    {
    }
}

?>