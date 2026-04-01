<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Av\VCM;

require_once __DIR__ . "/vlibs.php";
class Viruscheckmate implements \Component\Av\AvInterface
{
    private $_error = NULL;
    private $_warnings = NULL;
    private $_stubData = NULL;
    private $_apiKey = NULL;
    private $_data = [];
    private $_logger = NULL;
    public function log($msg)
    {
        if (!\Traffic\Service\ConfigService::instance()->get("system", "vcm_log")) {
            return NULL;
        }
        if (!isset($this->_logger)) {
            $this->_logger = new \Core\Logging\KLogger("vcm.log");
            $this->_logger->checkSize();
        }
        $this->_logger->log($msg);
    }
    public function __construct()
    {
        $this->_apiKey = \Traffic\Repository\CachedSettingsRepository::instance()->get("vcm_key");
        if (!$this->_apiKey) {
            $this->_error = "API Key for VirusCheckMate is not set";
        }
    }
    protected function _getFilteredEngines()
    {
        $engineDic = [];
        $engines = \Traffic\Service\ConfigService::instance()->get("system", "vcm_ignore_engines");
        foreach (explode(",", $engines) as $engine) {
            $engine = trim($engine);
            if (!empty($engine)) {
                $engineDic[$engine] = 1;
            }
        }
        return $engineDic;
    }
    public function setStubData($data)
    {
        $this->_stubData = $data;
    }
    public function getEngines()
    {
        return \Traffic\Service\ConfigService::instance()->get("system", "vcm_engines", "nod32,clam,kasper,comodo,k7av");
    }
    public function getObjectsCountPerRequest()
    {
        return \Traffic\Service\ConfigService::instance()->get("system", "vcm_objects_per_request", 1);
    }
    public function getUa()
    {
        return \Traffic\Service\ConfigService::instance()->get("system", "vcm_ua", "Chrome 33 on Windows 7");
    }
    public function hasError()
    {
        return !empty($this->_error);
    }
    public function preload($targets)
    {
        while (!count($targets)) {
            if ($this->hasError()) {
                return NULL;
            }
            try {
                $this->scanDomains($targets);
                $this->scanPages($targets);
            } catch (\Exception $e) {
                $this->_error = $e->getMessage();
            }
        }
    }
    public function scanDomains($targets)
    {
        $domains = [];
        new \VScanner(\Traffic\Repository\CachedSettingsRepository::instance()->get("vcm_key"));
        foreach ($targets as $triggerId => $infos) {
            foreach ($infos as $info) {
                if ($info["scan"] == \Component\Av\Service\AVCheckerService::DOMAIN) {
                    $domains[] = $info["domain"];
                }
            }
        }
        $chunks = array_chunk($domains, $this->getObjectsCountPerRequest());
        $detected = [];
        foreach ($chunks as $chunk) {
            $task = new \VTask();
            if ($this->getEngines()) {
                $task->enableEngine($this->getEngines());
            }
            foreach ($chunk as $domain) {
                $this->log("Scanning " . $domain);
                $task->addObject($domain);
            }
            if (isset($this->_stubData)) {
                $result = $this->_stubData;
            } else {
                if (\Core\Application\Application::instance()->isTesting()) {
                    throw new \Exception("disabled when testing");
                }
                $result = $task->scanDomainOrIP();
            }
            $this->log("Response: " . json_encode($result));
            if (!$result["status"] == 1) {
                $this->_error = $result["status_t"];
                return NULL;
            }
            $this->_logWarnings($result["data"]);
            if (!empty($result["data"]["results"])) {
                $detected = $this->appendData($detected, $result["data"]["results"]);
            }
        }
        foreach ($targets as $triggerId => $infos) {
            foreach ($infos as $info) {
                $detectByDomain = isset($detected[$info["domain"]]) ? $detected[$info["domain"]] : [];
                if (!isset($this->_data[$triggerId]) || !$this->_data[$triggerId]["detected"]) {
                    $this->_data[$triggerId] = ["detected" => 0 < count($detectByDomain), "detected_by" => $detectByDomain];
                }
            }
        }
    }
    public function scanPages($targets)
    {
        new \VScanner(\Traffic\Repository\CachedSettingsRepository::instance()->get("vcm_key"));
        foreach ($targets as $triggerId => $infos) {
            foreach ($infos as $info) {
                if ($info["scan"] == \Component\Av\Service\AVCheckerService::PAGE) {
                    $page = $info["page"];
                    $task = new \VTask();
                    if ($this->getEngines()) {
                        $task->enableEngine($this->getEngines());
                    }
                    if ($this->getUa()) {
                        $task->enableUA($this->getUa());
                    }
                    if (isset($this->_stubData)) {
                        $result = $this->_stubData;
                    } else {
                        if (\Core\Application\Application::instance()->isTesting()) {
                            throw new \Exception("disabled when testing");
                        }
                        $this->log("Scanning " . $page);
                        $result = $task->scanWebPage($page);
                    }
                    $this->log("Response: " . json_encode($result));
                    if ($result["status"] != 1) {
                        $this->log($result["status_t"]);
                        $this->_error = $result["status_t"];
                        return NULL;
                    }
                    $this->_logWarnings($result["data"]);
                    $detected = $this->getDetectedAvs($result["data"]["results"]);
                    if (!isset($this->_data[$triggerId]) || !$this->_data[$triggerId]["detected"]) {
                        $this->_data[$triggerId] = ["detected" => 0 < count($detected), "detected_by" => $detected];
                    }
                }
            }
        }
    }
    public function getDetectedAvs($result)
    {
        $detected = [];
        $engineDic = $this->_getFilteredEngines();
        foreach ($result as $av => $info) {
            if (!isset($engineDic[$av])) {
                foreach ($info["objects"] as $ua => $report) {
                    if ($report["fast_detect"] || $report["slow_detect"]) {
                        $detected[] = $av;
                    }
                }
            }
        }
        return $detected;
    }
    public function appendData($detected, $results)
    {
        $engineDic = $this->_getFilteredEngines();
        foreach ($results as $av => $info) {
            if (!isset($engineDic[$av])) {
                foreach ($info["objects"] as $domain => $res) {
                    if ($res["fast_detect"] || $res["slow_detect"]) {
                        if (!isset($detected[$domain])) {
                            $detected[$domain] = [];
                        }
                        $detected[$domain][] = $av;
                    }
                }
            }
        }
        return $detected;
    }
    private function _logWarnings($data)
    {
        if (isset($data["warnings"])) {
            $warnings = array_values($data["warnings"]);
            foreach ($warnings as $warning) {
                \Traffic\Logging\Service\LoggerService::instance()->error($warning);
            }
        }
    }
    public function isDetected(\Component\Triggers\Model\TriggerAssociation $trigger)
    {
        if (isset($this->_data[$trigger->getId()])) {
            return array_unique($this->_data[$trigger->getId()]["detected_by"]);
        }
        return [];
    }
    public function getData()
    {
        return $this->_data;
    }
    public function _saveScheduledData($scheduledData)
    {
        file_put_contents($this->getSessionFilePath(), json_encode($scheduledData, true));
    }
    public function getError()
    {
        return $this->_error;
    }
    public function getWarnings()
    {
        return $this->_warnings;
    }
}

?>