<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

class VScanner
{
    private $curl_handle = NULL;
    private $_apikey = NULL;
    private $_server = NULL;
    private $_lastrequestraw = NULL;
    private $_dataresponseraw = NULL;
    protected $_allowed_methods = ["service" => ["get"], "check" => ["new", "get"]];
    const API_VERSION = 1;
    const ENGINE_TYPE_FILE = 1;
    const ENGINE_TYPE_ADDRESS = 2;
    const default_server = "http://viruscheckmate.com";
    private function _curl_reset()
    {
        curl_close($this->curl_handle);
        $this->curl_handle = curl_init();
        curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, true);
    }
    private function _filterHTTPCode($http_code)
    {
    }
    private function _request($method, $submethod = "get", $task_id = NULL, $post = NULL)
    {
        $this->_curl_reset();
        if (!array_key_exists($method, $this->_allowed_methods)) {
            throw new Exception("Unknown method '" . $method . "'");
        }
        if (!in_array($submethod, $this->_allowed_methods[$method])) {
            throw new Exception("Unknown submethod '" . $submethod . "' of method '" . $method . "'");
        }
        $current_request = "/api/v1" . "/" . $method . "/" . $submethod . "/";
        if ($task_id !== NULL) {
            $current_request .= $task_id . "/";
        }
        $url = $this->_server . $current_request;
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($this->curl_handle, CURLOPT_TIMEOUT, 10);
        if ($post !== NULL) {
            curl_setopt($this->curl_handle, CURLOPT_POST, true);
            curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $post);
        }
        $result = curl_exec($this->curl_handle);
        if ($result === false) {
            throw new Exception(curl_error($this->curl_handle));
        }
        $curl_exec_info = curl_getinfo($this->curl_handle);
        $return_array = json_decode($result, true);
        if ($return_array === NULL) {
            VScanner::_filterHTTPCode($curl_exec_info["http_code"]);
            throw new Exception("Unkown error in response");
        }
        if (!isset($return_array["status"])) {
            throw new Exception("Unkown response");
        }
        if ($return_array["status"] !== 1) {
            throw new Exception($return_array["status_t"]);
        }
        return $return_array;
    }
    private function _getEngineProp($Engine, $Prop)
    {
        if (!$this->_lastrequestraw) {
            $this->getInfo();
        }
        foreach ($this->_lastrequestraw["data"]["engines"] as $_engine) {
            if ($Engine == $_engine["short_name"]) {
                if (isset($_engine[$Prop])) {
                    return $_engine[$Prop];
                }
                return false;
            }
        }
        return false;
    }
    public function getTaskData($task_id)
    {
        VTask::_filterTaskId($task_id);
        $this->_dataresponseraw = $this->_request("check", "get", $task_id, ["apikey" => $this->_apikey]);
        return $this->_dataresponseraw;
    }
    public function getInfo($task_id = NULL)
    {
        if ($task_id !== NULL) {
            VTask::_filterTaskId($task_id);
        }
        $this->_lastrequestraw = $this->_request("service", "get", $task_id, ["apikey" => $this->_apikey]);
        return $this->_lastrequestraw;
    }
    public function getEngineFullName($Engine)
    {
        return $this->_getEngineProp($Engine, "full_name");
    }
    public function getEngineVersion($Engine)
    {
        return $this->_getEngineProp($Engine, "version");
    }
    public function getEngineUpdate($Engine)
    {
        return $this->_getEngineProp($Engine, "def_time");
    }
    public function getAvailableEngines($CheckType, $Engine = NULL)
    {
        if (!$this->_lastrequestraw) {
            $this->getInfo();
        }
        $_engines = [];
        foreach ($this->_lastrequestraw["data"]["engines"] as $_engine) {
            if ($_engine["status"] == 1 && $CheckType & $_engine["type"]) {
                if ($Engine != NULL) {
                    if ($Engine == $_engine["short_name"]) {
                        return true;
                    }
                } else {
                    $_engines[] = $_engine["short_name"];
                }
            }
        }
        if ($Engine != NULL || count($_engines) == 0) {
            return false;
        }
        return $_engines;
    }
    public function _requestScan($params)
    {
        $_params = $params;
        $_params["apikey"] = $this->_apikey;
        return $this->_request("check", "new", NULL, $_params);
    }
    public function __construct($apikey, $server = self::default_server)
    {
        if (!function_exists("curl_init")) {
            throw new Exception("Please, enable curl");
        }
        Vscanner::_filterAPIKey($apikey);
        $ch = curl_init();
        if ($ch === false) {
            throw new Exception("Curl initialization error");
        }
        $this->curl_handle = $ch;
        $this->_server = $server;
        $this->_apikey = $apikey;
        self::$scanner = $this;
    }
    public function __destruct()
    {
        curl_close($this->curl_handle);
    }
    public static function _filterAPIKey($task_id)
    {
        if (!preg_match("/^[a-z0-9]{40}\$/", $task_id)) {
            throw new Exception("Wrong 'APIKey'");
        }
    }
}
class VTask
{
    private $_task_id = NULL;
    private $_detectCount = NULL;
    private $_detectedObjects = NULL;
    private $_detectedEngines = NULL;
    private $_cleanObjects = NULL;
    private $_cleanEngines = NULL;
    private $_cleanItems = NULL;
    private $_detectedItems = NULL;
    private $_allItems = NULL;
    private $_usedEngines = NULL;
    private $_lastrequestraw = NULL;
    private $_type = NULL;
    private $_fileUpload = NULL;
    private $_inputObjects = NULL;
    private $_engineSet = NULL;
    private $_uaSet = NULL;
    protected $_STATUSES = ["18446744073709551615" => "ERROR", "0" => "NOT_READY", "1" => "CHECKING", "2" => "CHECKING", "3" => "CHECKING", "4" => "DONE", "5" => "DONE", "6" => "DONE"];
    private $_options = ["use_profile" => "", "uagents" => "", "option_fast" => false, "option_usecache" => false, "option_autocheck" => false, "option_unpack" => false, "option_resolve" => false, "response_type" => "on_close"];
    public static $scanner = NULL;
    public function __construct($task_id = NULL, $apikey = NULL)
    {
        if ($task_id !== NULL) {
            VTask::_filterTaskId($task_id);
            $this->_task_id = $task_id;
        }
        if ($apikey !== NULL) {
            self::$scanner = new VScanner($apikey);
        } else {
            if (self::$scanner == NULL) {
                throw new Exception("Initialized scanner not found, need APIKEY");
            }
        }
        $this->_inputObjects = [];
        $this->_engineSet = [];
        $this->_uaSet = [];
        $this->_fileUpload = false;
    }
    public function addObject($object)
    {
        if (!is_string($object) || $object == "") {
            throw new Exception("Wrong object");
        }
        if (in_array($object, $this->_inputObjects)) {
            throw new Exception("Object already added");
        }
        $this->_inputObjects[] = trim($object);
    }
    public function setOption($option, $param)
    {
        if (!isset($this->_options[$option])) {
            throw new Exception("Wrong option");
        }
        $this->_options[$option] = $param;
    }
    public function enableUA($UA)
    {
        $_newua = explode(",", $UA);
        foreach ($_newua as $_ua) {
            if (strlen($_ua) >= 3) {
                if (!in_array($_ua, $this->_uaSet)) {
                    $this->_uaSet[] = $_ua;
                }
            }
        }
    }
    public function clearUASet()
    {
        $this->_uaSet = [];
    }
    public function setProfile($profile)
    {
        $this->cleanEngineSet();
        $this->setOption("use_profile", trim($profile));
    }
    public function clearProfile()
    {
        $this->setOption("use_profile", "");
    }
    public function enableEngine($engines)
    {
        $this->clearProfile();
        $_newengines = explode(",", $engines);
        foreach ($_newengines as $_engine) {
            if (strlen($_engine) >= 3) {
                if (!in_array($_engine, $this->_engineSet)) {
                    $this->_engineSet[] = trim($_engine);
                }
            }
        }
    }
    public function cleanEngineSet()
    {
        $this->_engineSet = [];
    }
    private function _scan()
    {
        $this->_options["task_type"] = $this->_type;
        switch ($this->_type) {
            case "link":
            case "webpage":
                $this->_options["url"] = $this->_inputObjects[0];
                break;
            case "file":
                if ($this->_fileUpload === true) {
                    foreach ($this->_inputObjects as $key => $file) {
                        $this->_options["file[" . $key . "]"] = "@" . realpath($file);
                    }
                }
                break;
            case "domain":
                if ($this->_fileUpload === true) {
                    $this->_options["file"] = "@" . realpath($this->_inputObjects[0]);
                } else {
                    if (1 < count($this->_inputObjects)) {
                        foreach ($this->_inputObjects as $key => $object) {
                            $this->_options["url[" . $key . "]"] = $object;
                        }
                    } else {
                        $this->_options["url"] = $this->_inputObjects[0];
                    }
                }
                break;
            default:
                $this->_fileUpload = false;
                if (0 < count($this->_engineSet)) {
                    $this->_options["use_profile"] = "";
                    $this->_options["engines"] = implode(",", $this->_engineSet);
                }
                if (0 < count($this->_uaSet) && $this->_type == "webpage") {
                    $this->_options["uagents"] = implode(",", $this->_uaSet);
                }
                $data = $this->getData(NULL, self::$scanner->_requestScan($this->_options));
                if ($this->_options["response_type"] == "on_close" || $this->_options["response_type"] == "") {
                    while (!$this->isDone()) {
                        sleep(1);
                        $this->getData();
                    }
                }
                return $data;
        }
    }
    public function scanDomainOrIP($object = NULL)
    {
        $this->_type = "domain";
        $this->_fileUpload = false;
        if ($object !== NULL) {
            $this->addObject($object);
        } else {
            if (count($this->_inputObjects) == 0) {
                throw new Exception("Need object to check");
            }
        }
        return $this->_scan();
    }
    public function scanLinkToFile($object = NULL)
    {
        $this->_type = "link";
        $this->_fileUpload = false;
        if ($object !== NULL) {
            $this->addObject($object);
        } else {
            if (count($this->_inputObjects) == 0) {
                throw new Exception("Need object to check");
            }
        }
        return $this->_scan();
    }
    public function scanDomainsFromList($object = NULL)
    {
        $this->_type = "domain";
        $this->_fileUpload = true;
        if ($object !== NULL) {
            $this->addObject($object);
        } else {
            if (count($this->_inputObjects) == 0) {
                throw new Exception("Need object to check");
            }
        }
        return $this->_scan();
    }
    public function scanWebPage($object = NULL)
    {
        $this->_type = "webpage";
        $this->_fileUpload = false;
        if ($object !== NULL) {
            $this->addObject($object);
        } else {
            if (count($this->_inputObjects) == 0) {
                throw new Exception("Need object to check");
            }
        }
        return $this->_scan();
    }
    public function scanFiles($object = NULL)
    {
        $this->_type = "file";
        $this->_fileUpload = true;
        if ($object !== NULL) {
            $this->addObject($object);
        } else {
            if (count($this->_inputObjects) == 0) {
                throw new Exception("Need object to check");
            }
        }
        return $this->_scan();
    }
    public function scanDirectory($Directory)
    {
        $this->_type = "file";
        $this->_fileUpload = true;
        if (!is_string($Directory) || strlen($Directory) == 0 || realpath($Directory) == "") {
            throw new Exception("Wrong directory");
        }
        $files = scandir(realpath($Directory));
        foreach ($files as $file) {
            if ($file[0] != ".") {
                $this->addObject(realpath($Directory . $file));
            }
        }
        if (count($this->_inputObjects) == 0) {
            throw new Exception("Need object to check");
        }
        return $this->_scan();
    }
    public function setTaskId($task_id)
    {
        VTask::_filterTaskId($task_id);
        $this->_task_id = $task_id;
    }
    public function getTaskId()
    {
        return $this->_task_id;
    }
    public function getInfo($task_id = NULL)
    {
        if ($task_id !== NULL) {
            VTask::_filterTaskId($task_id);
            $this->_task_id = $task_id;
        }
        $rawdata = self::$scanner->getInfo($this->_task_id);
        return $rawdata;
    }
    public function getData($task_id = NULL, $rawdata = NULL)
    {
        if ($rawdata === NULL) {
            if ($task_id === NULL && $this->_task_id === NULL) {
                throw new Exception("TaskId unknown");
            }
            if ($task_id !== NULL) {
                VTask::_filterTaskId($task_id);
                $this->_task_id = $task_id;
            }
        }
        if ($rawdata) {
            $this->_lastrequestraw = $rawdata;
        } else {
            $this->_lastrequestraw = self::$scanner->getTaskData($this->_task_id);
        }
        $this->_task_id = $this->_lastrequestraw["data"]["info"]["check_id"];
        $results_section = $this->_lastrequestraw["data"]["results"];
        $objects_section = $this->_lastrequestraw["data"]["objects"];
        $this->_detectCount = 0;
        $this->_detectedObjects = [];
        $this->_detectedEngines = [];
        $this->_cleanObjects = [];
        $this->_cleanEngines = [];
        $this->_cleanItems = [];
        $this->_detectedItems = [];
        $this->_allItems = [];
        $this->_usedEngines = [];
        if (isset($this->_lastrequestraw["data"]["results"])) {
            foreach ($results_section as $engine => $objects) {
                foreach ($objects["objects"] as $object => $result) {
                    if ($engine != NULL) {
                        if (!in_array($engine, $this->_usedEngines)) {
                            $this->_usedEngines[] = $engine;
                        }
                        if ($result["fast_detect"] == 1) {
                            $this->_detectCount++;
                            if (in_array($engine, $this->_detectedEngines) === false) {
                                $this->_detectedEngines[$engine][] = $object;
                            }
                            if (in_array($object, $this->_detectedObjects) === false) {
                                $this->_detectedObjects[$object][$engine] = $result["detect_name"];
                            }
                        } else {
                            if (in_array($engine, $this->_cleanEngines) === false) {
                                $this->_cleanEngines[$engine][] = $object;
                            }
                            if (in_array($object, $this->_cleanObjects) === false) {
                                $this->_cleanObjects[$object][] = $engine;
                            }
                        }
                    }
                }
            }
        }
        if (isset($this->_lastrequestraw["data"]["objects"])) {
            foreach ($objects_section as $num => $object) {
                $object_name = $object["object"];
                $this->_allItems[$object_name] = ["size" => $object["size"], "md5" => $object["md5"], "sha1" => $object["sha1"], "type" => $object["type"]];
                if (key_exists($object_name, $this->_detectedObjects)) {
                    $this->_detectedItems[$object_name] = $this->_detectedObjects[$object_name];
                } else {
                    $this->_cleanItems[] = $object_name;
                }
            }
        }
        return $this->_lastrequestraw;
    }
    public function _firstrequest()
    {
        if (!$this->_lastrequestraw && $this->_lastrequestraw["data"]["info"]["status"] != NULL) {
            if ($this->_task_id === NULL) {
                throw new Exception("TaskId unknown");
            }
            $this->getData();
        }
    }
    public function getStatus()
    {
        $this->_firstrequest();
        $STATUS_INT = $this->_lastrequestraw["data"]["info"]["status"];
        return $this->_STATUSES[$STATUS_INT];
    }
    public function isDone()
    {
        $this->_firstrequest();
        $STATUS_INT = $this->_lastrequestraw["data"]["info"]["status"];
        if (!$STATUS_INT) {
            return false;
        }
        return $this->_STATUSES[$STATUS_INT] != "CHECKING" && $this->_STATUSES[$STATUS_INT] != "NOT_READY";
    }
    public function isError()
    {
        $this->_firstrequest();
        $STATUS_INT = $this->_lastrequestraw["data"]["info"]["status"];
        return $this->_STATUSES[$STATUS_INT] == "ERROR" || $this->_STATUSES[$STATUS_INT] == "NOT_READY";
    }
    public function isDetected()
    {
        $this->_firstrequest();
        if (0 < count($this->_detectedObjects)) {
            return true;
        }
        return false;
    }
    public function isClean()
    {
        return !$this->isDetected();
    }
    public function getCleanItems()
    {
        $this->_firstrequest();
        return $this->_cleanItems;
    }
    public function getDetectedItems()
    {
        $this->_firstrequest();
        return $this->_detectedItems;
    }
    public function getItems()
    {
        $this->_firstrequest();
        return $this->_allItems;
    }
    public function getDetectedEngines()
    {
        $this->_firstrequest();
        return $this->_detectedEngines;
    }
    public function getUsedEngines()
    {
        $this->_firstrequest();
        return $this->_usedEngines;
    }
    public function saveCleanToFile($FileName = "clean.txt", $glue = "\r\n")
    {
        $_items = $this->getCleanItems();
        $_content = implode($glue, $_items);
        if (file_put_contents($FileName, $_content) === false) {
            throw new Exception("file_put_contents() Error writing file");
        }
    }
    public static function _filterTaskId($task_id)
    {
        if (!preg_match("/^[a-z0-9]{12}\$/i", $task_id)) {
            throw new Exception("Wrong 'task_id'");
        }
    }
}

?>