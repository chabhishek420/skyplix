<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\SelfUpdate\SelfChecker;

class SelfChecker
{
    private $_output = NULL;
    private $_fail = false;
    public function criticalErrorExists()
    {
        return $this->_fail;
    }
    public function checkAll()
    {
        $this->checkMigrations();
        $this->resetOpCache();
        $this->restartRoadRunner();
        $this->_writeLn("");
        $this->checkPermissions();
        $this->_writeLn("");
        $this->checkGeoDbs();
        $this->updateCache();
        $this->fixTfFile();
        return $this->_output;
    }
    public function checkMigrations()
    {
        $this->_write(\Core\Locale\LocaleService::t("self_check.checking_migrations") . " - ");
        if (!\Component\Migrations\Service\MigrationRunnerService::instance()->isAllApplied()) {
            $runner = new \Component\Migrations\MigrationRunner\MigrationRunner();
            $runner->runAll();
        }
        $this->_writeLn(\Core\Locale\LocaleService::t("self_check.applied"), "success");
    }
    public function checkPermissions()
    {
        $paths = ["var", "var/log", "var/stores", "exports", \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::LP_DIR)];
        $success = true;
        $errors = [];
        foreach ($paths as $path) {
            $fullPath = ROOT . "/" . $path;
            if (!is_writable($fullPath)) {
                \Core\FileSystem\Service\FileSystemService::instance()->mkdir($fullPath, 511);
                if (!is_writable($fullPath)) {
                    $errors[] = \Core\Locale\LocaleService::t("self_check.not_writable", [$path]);
                    $success = false;
                    $this->fail();
                }
            }
        }
        $this->_write(\Core\Locale\LocaleService::t("self_check.checking_permissions") . " - ");
        if ($success) {
            $this->_writeLn("ok", "success");
        } else {
            $this->_writeLn(\Core\Locale\LocaleService::t("self_check.error"), "danger");
            $this->_writeLn($errors, "danger");
        }
    }
    public function checkGeoDbs()
    {
        $dbs = [\Component\GeoDb\Ip2Location\Ip2LocationDb3Lite::ID, \Component\GeoDb\Keitaro\KeitaroBotDb2::ID, \Component\GeoDb\Keitaro\KeitaroCarrierDb::ID];
        $allOk = true;
        $downloads = [];
        foreach ($dbs as $id) {
            $db = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->getDb($id);
            if (!$db->exists()) {
                \Traffic\GeoDb\Service\GeoDbService::instance()->update($db);
                $downloads[] = "Db downloaded: " . $id;
                $allOk = false;
            }
        }
        $this->_write(\Core\Locale\LocaleService::t("self_check.checking_dbs") . " - ");
        if ($allOk) {
            $this->_writeLn("ok", "success");
        } else {
            $this->_writeLn(\Core\Locale\LocaleService::t("self_check.downloading"), "danger");
            $this->_writeLn($downloads, "success");
        }
    }
    private function fail()
    {
        $this->_fail = true;
    }
    public function _write($msg, $status = NULL)
    {
        if (is_array($msg)) {
            $msg = "- " . implode(PHP_EOL . "- ", $msg);
        }
        if ($status) {
            $msg = "<span class=\"text-" . $status . "\">" . $msg . "</span>";
        }
        $this->_output .= $msg;
    }
    private function _writeLn($msg, $status = NULL)
    {
        $this->_write($msg, $status);
        $this->_write(PHP_EOL);
    }
    public function updateCache()
    {
        \Traffic\Repository\CachedSettingsRepository::instance()->warmup();
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
    }
    public function fixTfFile()
    {
        if (!\Core\Application\TsService::instance()->isFileExists()) {
            \Core\Application\TsService::instance()->saveTimestamp(\Core\Application\FeatureService::instance()->getLicenseExpireTime()->getTimestamp());
        }
    }
    public function resetOpCache()
    {
        if (function_exists("opcache_reset")) {
            opcache_reset();
        }
    }
    public function restartRoadRunner()
    {
        \Traffic\RoadRunner\Server::restart();
    }
}

?>