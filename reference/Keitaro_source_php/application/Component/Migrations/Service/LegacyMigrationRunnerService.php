<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Service;

class LegacyMigrationRunnerService extends \Traffic\Service\AbstractService
{
    protected $_version = NULL;
    private $_lockTimeout = NULL;
    public function setLockTimeout($value)
    {
        $this->_lockTimeout = $value;
    }
    public function checkRowExistence()
    {
        $sql = "SELECT COUNT(*) FROM schema_version";
        $cnt = \Core\Db\Db::instance()->getOne($sql);
        if (!$cnt) {
            $sql = "INSERT IGNORE INTO schema_version (`version`) VALUES (0)";
            \Core\Db\Db::instance()->execute($sql);
        }
    }
    public function getCurrentVersion()
    {
        if ($this->_version) {
            return $this->_version;
        }
        $this->checkRowExistence();
        $sql = "SELECT `version` FROM schema_version ORDER BY `version` DESC";
        $version = \Core\Db\Db::instance()->getOne($sql);
        return $version;
    }
    public function updateCurrentVersion($version)
    {
        $this->checkRowExistence();
        $sql = "UPDATE schema_version SET `version` = " . (int) $version . " LIMIT 1";
        \Core\Db\Db::instance()->execute($sql);
        $this->_version = (int) $version;
    }
    public function getLastVersion()
    {
        $migrations = $this->getMigrations();
        $migration = array_pop($migrations);
        return $migration->getVersion();
    }
    public function getMigrations()
    {
        return \Component\Migrations\Repository\LegacyMigrationsRepository::instance()->getMigrations();
    }
    public function notExecutedMigrationIsExists()
    {
        $migrations = array_keys($this->getMigrations());
        $last = $migrations[count($migrations) - 1];
        return $this->getCurrentVersion() < $last;
    }
    public function run($version)
    {
        $migration = \Component\Migrations\Repository\LegacyMigrationsRepository::instance()->getMigration($version);
        if (!isset($migration)) {
            throw new \Exception("Migration " . $version . " is not exists");
        }
        \Core\Db\Db::instance()->beginTransaction();
        try {
            $migration->run();
            \Core\Db\Db::instance()->commit();
            if ($this->getCurrentVersion() < $version) {
                $this->updateCurrentVersion($version);
            }
        } catch (\Exception $e) {
            \Core\Db\Db::instance()->rollback();
            throw $e;
        }
    }
    public function runAll()
    {
        foreach ($this->getMigrations() as $migration) {
            if ($this->getCurrentVersion() < $migration->getVersion()) {
                $this->run($migration->getVersion());
            }
        }
    }
}

?>