<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\MigrationRunner;

class MigrationRunner
{
    private $_lock = NULL;
    private $_lockCount = 0;
    public function run($name)
    {
        set_time_limit(0);
        $this->_acquireLock();
        $migration = \Component\Migrations\Repository\MigrationsRepository::instance()->getMigration($name);
        if (!isset($migration)) {
            throw new \Exception("Migration " . $name . " is not exists");
        }
        \Core\Db\Db::instance()->beginTransaction();
        try {
            $migration->run(\Component\Migrations\Service\MigrationRunnerService::instance()->isMigrationApplied($migration));
            \Core\Db\Db::instance()->commit();
            \Component\Migrations\Service\MigrationRunnerService::instance()->setMigrationApplied($migration);
            if (\Core\Application\Application::instance()->isDevelopment()) {
                \Component\Migrations\Service\SqlDumpService::instance()->dump();
            }
            if (\Component\Migrations\Service\TokuDbService::instance()->isClicksTableInTokuDB()) {
                \Component\Migrations\Service\TokuDbService::instance()->updateEngine();
            }
            $this->_unlock();
        } catch (\Exception $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
            \Core\Db\Db::instance()->rollback();
            $this->_unlock(true);
            throw $e;
        }
    }
    public function runAll($runLegacy = true)
    {
        $this->_acquireLock();
        if ($runLegacy) {
            \Component\Migrations\Service\LegacyMigrationRunnerService::instance()->runAll();
        }
        foreach (\Component\Migrations\Repository\MigrationsRepository::instance()->getMigrations() as $migration) {
            if (!\Component\Migrations\Service\MigrationRunnerService::instance()->isMigrationApplied($migration)) {
                $this->run($migration->getName());
            }
        }
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
        $this->_unlock();
    }
    protected function _acquireLock()
    {
        if (!$this->_lock) {
            $this->_lock = \Core\Lock\LockService::instance()->waitForLock(\Component\DelayedCommands\CronTask\ExecuteDelayedCommand::LOCK_NAME);
        }
        $this->_lockCount++;
    }
    protected function _unlock($force = false)
    {
        $this->_lockCount--;
        if (($this->_lockCount <= 0 || $force) && $this->_lock) {
            \Core\Lock\LockService::instance()->unlock($this->_lock, \Component\DelayedCommands\CronTask\ExecuteDelayedCommand::LOCK_NAME);
            unset($this->_lock);
        }
    }
}

?>