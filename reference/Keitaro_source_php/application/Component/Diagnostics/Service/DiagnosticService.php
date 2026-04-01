<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Diagnostics\Service;

class DiagnosticService extends \Traffic\Service\AbstractService
{
    const CRITICAL = "critical";
    const INFO = "info";
    const TIME_TO_WAIT_FOR_CRON_RUN = 60;
    const FILE_CHECK_INTERVAL = 604800;
    const INSTALLED_AT = "installed_at";
    const RECOMMENDED_FREE_SPACE = 10;
    const GB = 1073741824;
    public function getStatus($trackerUrl)
    {
        if (empty($trackerUrl)) {
            throw new \Exception("trackerUrl cannot be empty");
        }
        if ($this->_shouldCheckFiles()) {
            $isConfigOk = $this->isConfigOk($trackerUrl);
            $isInstallerOk = $this->isInstallerOk($trackerUrl);
            if ($isConfigOk && $isInstallerOk) {
                $this->_updateLastFileCheckTime();
            }
        } else {
            $isConfigOk = true;
            $isInstallerOk = true;
        }
        $external = \Core\Locale\LocaleService::instance()->getLanguage() == \Core\Locale\LocaleService::EN ? "https://blog.keitaro.io/en/php-server-upgrade/" : "https://blog.keitaro.io/ru/php-server-upgrade/";
        $probes = [["id" => "migrations", "exists" => !$this->isMigrationsOk(), "url" => "migrations.index", "level" => CRITICAL], ["id" => "server_upgrade", "exists" => $this->isServerUpgradeRequired(), "external_url" => $external, "level" => CRITICAL], ["id" => "var_dir", "exists" => !$this->isDirWritable(ROOT . "/var"), "level" => INFO], ["id" => "cron", "exists" => !$this->isCronOk(), "action" => "status", "level" => CRITICAL], ["id" => "config", "exists" => !$isConfigOk, "level" => CRITICAL], ["id" => "installer", "exists" => !$isInstallerOk, "level" => CRITICAL], ["id" => "no_free_space", "action" => "status", "exists" => !$this->hasFreeSpace(), "free_space" => \Component\System\Service\StatusService::instance()->getFreeSpace(), "level" => CRITICAL]];
        $result = ["ok" => true, "problems" => []];
        foreach ($probes as $key => $data) {
            if ($data["exists"]) {
                $result["problems"][] = $data;
            }
        }
        if (count($result["problems"])) {
            $result["ok"] = false;
        }
        $result["update"] = $this->isUpdateAvailable();
        return $result;
    }
    public function isMigrationsOk()
    {
        return \Component\Migrations\Service\LegacyMigrationRunnerService::instance()->getCurrentVersion() == \Component\Migrations\Service\LegacyMigrationRunnerService::instance()->getLastVersion() && \Component\Migrations\Service\MigrationRunnerService::instance()->isAllApplied();
    }
    public function isDirWritable($dir)
    {
        return is_writable($dir);
    }
    public function isCronOk()
    {
        $lastRun = \Component\Cron\Service\CronService::instance()->getLastRun();
        $checkDate = new \DateTime("-" . TIME_TO_WAIT_FOR_CRON_RUN . " minutes");
        if ($lastRun) {
            return $checkDate < $lastRun;
        }
        if (\Traffic\Repository\CachedSettingsRepository::instance()->get(INSTALLED_AT)) {
            $installedAt = new \DateTime(\Traffic\Repository\CachedSettingsRepository::instance()->get(INSTALLED_AT));
            $checkWith = new \DateTime("-5 minutes");
            return $checkWith < $installedAt;
        }
        return false;
    }
    public function isUpdateAvailable($version = TDS_VERSION)
    {
        return \Component\SelfUpdate\Service\SystemUpdaterService::instance()->isUpdateAvailable($version);
    }
    public function isInstallerOk($trackerUrl)
    {
        $installer = NULL;
        try {
            $url = $trackerUrl . "/install.php";
            $installer = \Traffic\Http\Service\HttpService::instance()->get($url)->getBody();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return !$installer || !strstr($installer, "compatibility-table");
        }
    }
    public function isConfigOk($trackerUrl)
    {
        $content = NULL;
        try {
            $url = $trackerUrl . "/application/config/config.ini";
            $content = \Traffic\Http\Service\HttpService::instance()->get($url)->getBody();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return !$content || !strstr($content, "password");
        }
    }
    public function isServerUpgradeRequired()
    {
        if (!\Core\Application\Application::instance()->isRoadRunnerRunning() && !\Component\System\Service\StatusService::instance()->isEngineRoadRunner()) {
            return false;
        }
        $fcgiExecutor = new \Core\Sandbox\CgiExecutor\FcgiExecutor(\Traffic\Service\ConfigService::instance()->get("system", \Traffic\Service\ConfigService::SANDBOX_FCGI_PATH), \Traffic\Service\ConfigService::instance()->get("system", \Traffic\Service\ConfigService::SANDBOX_FPM_PATH));
        return !$fcgiExecutor->isAvailable();
    }
    public function hasFreeSpace($currentFreeSpace = NULL, $totalSpace = NULL)
    {
        if (empty($currentFreeSpace)) {
            $currentFreeSpace = \Component\System\Service\StatusService::instance()->getFreeSpace();
        }
        if (empty($totalSpace)) {
            $totalSpace = \Component\System\Service\StatusService::instance()->getTotalSpace();
        }
        return RECOMMENDED_FREE_SPACE < $currentFreeSpace / $totalSpace * 100 || GB < $currentFreeSpace;
    }
    private function _shouldCheckFiles()
    {
        return FILE_CHECK_INTERVAL < time() - \Traffic\Repository\CachedSettingsRepository::instance()->get("diagnostic_last_file_check");
    }
    private function _updateLastFileCheckTime()
    {
        \Traffic\Service\SettingsService::instance()->updateValue("diagnostic_last_file_check", time());
    }
}

?>