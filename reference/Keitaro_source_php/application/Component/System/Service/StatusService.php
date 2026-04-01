<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\Service;

class StatusService extends \Traffic\Service\AbstractService
{
    private $_approvedKeitaroInstallations = ["/var/www/keitaro", "/Dev/keitaro/keitaro-app", "/data/public", "/builds/apliteni/keitaro/keitaro-app"];
    private $_statsFile = NULL;
    private $_statsInfo = NULL;
    private $_mockedInstallationMethod = NULL;
    private $_versionsContainsBrokenFcgi = ["1.8"];
    const CUSTOM = "Custom";
    const APPROVED = "Approved";
    const ENGINE_ROADRUNNER = "roadrunner";
    const WINNT = "WINNT";
    const DARWIN = "Darwin";
    const TOTAL_SIZE_IF_NOT_AVAILABLE = 1215752191;
    const RR_ENV_PARAM = "RR";
    const FCGI_SECTION = "fcgi";
    const FCGI_INSTALLED_KEY = "installed";
    const FCGI_SOCKET_KEY = "socket";
    const VERSIONS_SECTION = "versions";
    const VERSION_KEY = "installer";
    public function __construct()
    {
        $this->setStatsFile(ROOT . "/var/stats.json");
        $this->_statsInfo = NULL;
    }
    public function setInstallationMethod($method)
    {
        $this->_mockedInstallationMethod = $method;
    }
    public function setStatsFile($filePath)
    {
        $this->_statsFile = $filePath;
        $this->_statsInfo = NULL;
    }
    public function getStatsFile()
    {
        return $this->_statsFile;
    }
    public function info()
    {
        return ["export_dir_size" => $this->dirSize(ROOT . \Component\Streams\ExportStreams::PATH), "log_dir_size" => $this->dirSize(ROOT . "/var/log"), "clicks" => $this->getApproxClickCount(), "free_space" => $this->getFreeSpace(), "total_space" => $this->getTotalSpace(), "conversions" => \Component\Conversions\Repository\ConversionRepository::instance()->count(), "queue_size" => (int) \Traffic\CommandQueue\Service\DelayedCommandService::instance()->queueSize(), "last_cron_run" => $this->getLastCronTimestamp(), "cache_storage" => $this->getCacheStorage(), "cache_size" => $this->getCacheSize(), "db_size" => \Core\Db\Db::instance()->size(), "is_cron_running" => \Component\Diagnostics\Service\DiagnosticService::instance()->isCronOk(), "tokudb_status" => \Component\Migrations\Service\TokuDbService::instance()->getStatus(), "installation_method" => $this->getInstallationMethod(), "php_engine" => $this->getPhpEngine(), "statuses" => $this->getEngineStatuses(), "cpu_load" => $this->getCpuLoad(), "cpu_info" => $this->getCpuInfo(), "uptime" => $this->getUptime(), "responses" => $this->getResponses(), "cpu_stolen" => $this->getCpuStolen(), "free_mem" => $this->getFreeMem(), "fcgi_status" => $this->isFCGIInstalled(), "build_info" => $this->getBuildVersion()];
    }
    public function getCurrentVersion()
    {
        $stats = $this->getStats();
        if (is_array($stats) && !empty($stats[VERSIONS_SECTION][VERSION_KEY])) {
            return $stats[VERSIONS_SECTION][VERSION_KEY];
        }
        return "";
    }
    public function dirSize($dir)
    {
        return \Traffic\Tools\Tools::getFolderSize($dir);
    }
    public function getLastCronTimestamp()
    {
        $date = \Component\Cron\Service\CronService::instance()->getLastRun();
        return $date ? $date->format("c") : NULL;
    }
    public function getCacheSize()
    {
        return (int) \Traffic\Cache\CacheService::instance()->totalSize();
    }
    public function getCacheStorage()
    {
        return \Traffic\Repository\CachedSettingsRepository::instance()->get("cache_storage");
    }
    public function getFreeSpace()
    {
        return disk_free_space(".");
    }
    public function getTotalSpace()
    {
        if (strstr(ini_get("disable_functions"), "disk_total_space")) {
            return TOTAL_SIZE_IF_NOT_AVAILABLE;
        }
        $total = disk_total_space(".");
        if (empty($total) || $total == 0) {
            $total = TOTAL_SIZE_IF_NOT_AVAILABLE;
        }
        return $total;
    }
    public function getApproxClickCount()
    {
        $dbName = \Core\Db\Db::quote(\Traffic\Service\ConfigService::instance()->get("db", "name"));
        $tableName = \Core\Db\Db::quote(\Traffic\Model\Click::getTableName());
        $sql = "SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = " . $dbName . " and TABLE_NAME = " . $tableName;
        return \Core\Db\Db::instance()->getOne($sql);
    }
    public function getUptime()
    {
        $stats = $this->getStats();
        if (!empty($stats["uptime"])) {
            return $stats["uptime"];
        }
        return NULL;
    }
    public function getInstallationMethod()
    {
        if (\Core\Application\Application::instance()->isDevelopment() && ($version = \Traffic\Service\ConfigService::instance()->get("dev", "install_version", false))) {
            return APPROVED . " (v" . $version . ")";
        }
        if ($this->_mockedInstallationMethod !== NULL) {
            return $this->_mockedInstallationMethod;
        }
        foreach ($this->_approvedKeitaroInstallations as $dir) {
            if (strstr(__DIR__, $dir)) {
                return APPROVED . " (v" . $this->getCurrentVersion() . ")";
            }
        }
        return CUSTOM;
    }
    public function isApprovedInstallation()
    {
        if (strpos($this->getInstallationMethod(), APPROVED) !== false) {
            return true;
        }
        return false;
    }
    public function getEngineStatuses()
    {
        $stats = $this->getStats();
        if (!empty($stats["statuses"])) {
            $result = [];
            $result["tracker"] = $stats["statuses"]["tracker"];
            $result["nontracker"] = $stats["statuses"]["nontracker"];
            unset($stats["statuses"]["tracker"]);
            unset($stats["statuses"]["nontracker"]);
            foreach ($stats["statuses"] as $key => $value) {
                $result[$key] = $value;
            }
            return $result;
        } else {
            return [];
        }
    }
    public function getCpuInfo()
    {
        $stats = $this->getStats();
        if (!empty($stats["cpu"])) {
            $ghz = number_format($stats["cpu"]["frequency_mhz"] / 0, 1, ".", "");
            return $ghz . "GHz, cores " . $stats["cpu"]["cpu_cores"] . ", " . $stats["cpu"]["model_name"];
        }
        return NULL;
    }
    public function getCpuLoad()
    {
        $result = -1;
        $lines = NULL;
        switch (PHP_OS) {
            case WINNT:
                $matches = NULL;
                exec("wmic.exe CPU get loadpercentage /Value", $lines);
                if (preg_match("/^LoadPercentage\\=(\\d+)\$/", $lines[2], $matches)) {
                    $result = $matches[1];
                }
                break;
            case DARWIN:
                $result = -1;
                break;
            default:
                $result = $this->_getLinuxCpuLoad();
                return number_format((int) $result, 2);
        }
    }
    public function getResponses()
    {
        $stats = $this->getStats();
        if (empty($stats["responses"]) || !is_array($stats["responses"])) {
            return NULL;
        }
        return array_filter($stats["responses"], function ($key) {
            return 400 <= $key;
        }, ARRAY_FILTER_USE_KEY);
    }
    public function getCpuStolen()
    {
        return floatval($this->getVmStats("st"));
    }
    public function getFreeMem()
    {
        return ($this->getRamStats("total") - $this->getRamStats("used")) * 1048576;
    }
    public function getVmStats($key)
    {
        $stats = $this->getStats();
        if (isset($stats["info"]) && is_array($stats["info"]) && isset($stats["info"]["vmstat"]) && is_array($stats["info"]["vmstat"]) && isset($stats["info"]["vmstat"][$key])) {
            return $stats["info"]["vmstat"][$key];
        }
        return NULL;
    }
    public function getRamStats($key)
    {
        $stats = $this->getStats();
        if (isset($stats["info"]) && is_array($stats["info"]) && isset($stats["info"]["memory"]) && is_array($stats["info"]["memory"]["ram"]) && isset($stats["info"]["memory"]["ram"][$key])) {
            return $stats["info"]["memory"]["ram"][$key];
        }
        return NULL;
    }
    public function getPhpEngine()
    {
        $stats = $this->getStats();
        if (isset($stats["engines"]) && isset($stats["engines"]["php_engine"])) {
            return $stats["engines"]["php_engine"];
        }
        return NULL;
    }
    public function isEngineRoadRunner()
    {
        return $this->getPhpEngine() == ENGINE_ROADRUNNER;
    }
    public function getStats()
    {
        if ($this->_statsInfo === NULL) {
            if (is_file($this->getStatsFile())) {
                $json = file_get_contents($this->getStatsFile());
                $json = preg_replace("/[[:cntrl:]]/", "", $json);
                if (!($this->_statsInfo = json_decode($json, true))) {
                    \Traffic\Logging\Service\LoggerService::instance()->warning("Can't decode stats.json: " . json_last_error_msg());
                }
            } else {
                $this->_statsInfo = [];
            }
        }
        return $this->_statsInfo;
    }
    public function isFCGIInstalled()
    {
        $stats = $this->getStats();
        $version = $this->getCurrentVersion();
        if (in_array($version, $this->_versionsContainsBrokenFcgi)) {
            return false;
        }
        return is_array($stats) && !empty($stats[FCGI_SECTION][FCGI_INSTALLED_KEY]);
    }
    public function getFCGISocketPath()
    {
        $stats = $this->getStats();
        if (is_array($stats) && !empty($stats[FCGI_SECTION][FCGI_SOCKET_KEY])) {
            return $stats[FCGI_SECTION][FCGI_SOCKET_KEY];
        }
        return "";
    }
    public function getBuildVersion($getBuildVersion)
    {
        $buildInfoFile = ROOT . "/application/BUILD";
        if (file_exists($buildInfoFile)) {
            return file_get_contents($buildInfoFile);
        }
        return NULL;
    }
    public function areStatTablesLocked($areStatTablesLocked)
    {
        $prefix = \Core\Db\Db::getPrefix();
        $tables = [$prefix . "clicks", $prefix . "ref_destinations", $prefix . "visitors"];
        $sql = "SHOW OPEN TABLES WHERE `Table` IN (" . implode(", ", \Core\Db\Db::quote($tables)) . ") AND In_use > 0";
        $result = \Core\Db\Db::instance()->fetchRows($sql);
        return !empty($result);
    }
    private function _getLinuxCpuLoad()
    {
        $load = 0;
        if (is_readable("/proc/stat")) {
            $statData1 = $this->_getServerLoadLinuxData();
            sleep(1);
            $statData2 = $this->_getServerLoadLinuxData();
            if (!is_null($statData1) && !is_null($statData2)) {
                $statData2[0] -= $statData1[0];
                $statData2[1] -= $statData1[1];
                $statData2[2] -= $statData1[2];
                $statData2[3] -= $statData1[3];
                $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];
                if ($cpuTime == 0) {
                    $load = 0;
                } else {
                    $load = 100 - $statData2[3] * 100 / $cpuTime;
                }
            }
        }
        return $load;
    }
    private function _getServerLoadLinuxData()
    {
        if (is_readable("/proc/stat")) {
            $stats = @file_get_contents("/proc/stat");
            if ($stats !== false) {
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);
                $stats = str_replace(["\r\n", "\n\r", "\r"], "\n", $stats);
                $stats = explode("\n", $stats);
                foreach ($stats as $statLine) {
                    $statLineData = explode(" ", trim($statLine));
                    if (5 <= count($statLineData) && $statLineData[0] == "cpu") {
                        return [$statLineData[1], $statLineData[2], $statLineData[3], $statLineData[4]];
                    }
                }
            }
        }
        return NULL;
    }
}

?>