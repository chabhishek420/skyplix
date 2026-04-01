<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Av\Avscan;

class Avscan implements \Component\Av\AvInterface
{
    private $_warnings = NULL;
    private $_data = NULL;
    private $_error = NULL;
    private $_stubContent = NULL;
    private $_apiKey = NULL;
    const HTTP_CONNECT_TIMEOUT = 180;
    const AVSCAN_URL = "http://avscan.ru/api/";
    const BLOCK_KEY = "block_";
    public function __construct()
    {
        $this->_apiKey = \Traffic\Repository\CachedSettingsRepository::instance()->get("avscan_key");
        if (!$this->_apiKey) {
            $this->_error = "AVScan has no api key";
        }
    }
    public function hasError()
    {
        return $this->_error;
    }
    public function preload($targets)
    {
        while ($this->hasError()) {
            $this->_data = NULL;
            $domains = [];
            foreach ($targets as $infos) {
                $domains = array_merge($domains, array_pluck($infos, "domain"));
            }
            if (!count($domains)) {
                return NULL;
            }
            try {
                $content = $this->getAvscanContent($this->_apiKey, $domains);
                if (preg_match("/<error code=\"[0-9]+\">(.*?)<\\/error>/", $content, $error)) {
                    $this->_error = $error[1];
                    return NULL;
                }
                $result = json_decode($content, true);
                if (!isset($result) || empty($result)) {
                    $this->_error = "500 Internal Error";
                    return NULL;
                }
                if (isset($result["error"])) {
                    $this->_error = $result["error"]["desc"];
                    return NULL;
                }
                $data = [];
                foreach ($targets as $triggerId => $infos) {
                    foreach ($infos as $target) {
                        $domain = strtolower($target["domain"]);
                        if (count($this->getEngines())) {
                            $detected = $this->_detectedOnlyBy($result[$domain], $this->getEngines());
                        } else {
                            $detected = $this->_detectedByAny($result[$domain]);
                        }
                        if (!isset($this->_data[$triggerId]) || !$this->_data[$triggerId]["detected"]) {
                            $data[$triggerId] = ["detected" => 0 < count($detected), "detected_by" => $detected];
                        }
                    }
                }
                $this->_data = $data;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                $error = "(" . $e->getCode() . ") " . $e->getMessage();
                \Traffic\Logging\Service\LoggerService::instance()->warning("AVSCAN: " . $error);
                $this->_error = $error;
                return NULL;
            }
        }
    }
    public function getEngines()
    {
        $engines = explode(",", \Traffic\Service\ConfigService::instance()->get("system", "avscan_engines", "kis, nod, avt, dwb"));
        return array_filter($engines, "trim");
    }
    public function getAvscanContent($apiKey, $domains)
    {
        if (isset($this->_stubContent) && !empty($this->_stubContent)) {
            return $this->_stubContent;
        }
        $url = AVSCAN_URL . "?key=" . $apiKey . "&method=scan&format=json&domains=" . implode("|", $domains);
        $content = \Traffic\Http\Service\HttpService::instance()->get($url, [], [\GuzzleHttp\RequestOptions::TIMEOUT => HTTP_CONNECT_TIMEOUT])->getBody();
        return $content;
    }
    public function stubContent($value)
    {
        $this->_stubContent = $value;
    }
    public function getError()
    {
        return $this->_error;
    }
    public function getWarnings()
    {
        return $this->_warnings;
    }
    public function getData()
    {
        return $this->_data;
    }
    public function isDetected(\Component\Triggers\Model\TriggerAssociation $trigger)
    {
        if (!$this->getData()) {
            return [];
        }
        $data = $this->getData();
        if (!isset($trigger)) {
            throw new \Core\Exception("no trigger");
        }
        if (!isset($data[$trigger->getId()])) {
            return [];
        }
        return $data[$trigger->getId()]["detected_by"];
    }
    private function _detectedOnlyBy($data, $engines)
    {
        $detected = [];
        foreach ($engines as $check) {
            $check = trim($check);
            if (isset($data[BLOCK_KEY . $check]) && $data[BLOCK_KEY . $check] == 1) {
                $detected[] = $check;
            }
        }
        return $detected;
    }
    private function _detectedByAny($data)
    {
        $detected = [];
        foreach ($data as $av => $check) {
            if (strstr($av, BLOCK_KEY) && $check == 1) {
                $detected[] = str_replace(BLOCK_KEY, "", $av);
            }
        }
        return $detected;
    }
}

?>