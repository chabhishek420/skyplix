<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Service;

class MigrationRunnerService extends \Traffic\Service\AbstractService
{
    protected $_applied = [];
    public function getMigrations()
    {
        return \Component\Migrations\Repository\MigrationsRepository::instance()->getMigrations();
    }
    public function setMigrationApplied($migration)
    {
        $sql = "INSERT IGNORE INTO schema_migrations(`name`) VALUES(\"" . $migration->getName() . "\")";
        \Core\Db\Db::instance()->execute($sql);
        $this->_applied[$migration->getName()] = 1;
    }
    public function __construct()
    {
        $cnf = \Traffic\Service\ConfigService::instance()->get("db");
        $sql = "SELECT count(*)\n                FROM information_schema.tables\n                WHERE table_schema = \"" . $cnf["name"] . "\"\n                    AND table_name = \"schema_migrations\"\n                LIMIT 1";
        $count = \Core\Db\Db::instance()->getOne($sql);
        if ($count) {
            $sql = "SELECT `name` FROM schema_migrations ORDER BY `name` DESC";
            $rows = \Core\Db\Db::instance()->getAll($sql);
            foreach ($rows as $row) {
                $this->_applied[$row["name"]] = 1;
            }
        }
    }
    public function isMigrationApplied(\Migration $migration)
    {
        return isset($this->_applied[$migration->getName()]);
    }
    public function isMigrationNameApplied($migrationName)
    {
        return isset($this->_applied[$migrationName]);
    }
    public function getAppliedList()
    {
        return array_keys($this->_applied);
    }
    public function isAllApplied()
    {
        foreach (\Component\Migrations\Repository\MigrationsRepository::instance()->getMigrations() as $migration) {
            if (!$this->isMigrationApplied($migration)) {
                return false;
            }
        }
        return true;
    }
    public function runAll($runLegacy = true)
    {
        sleep(4);
    }
}

?>