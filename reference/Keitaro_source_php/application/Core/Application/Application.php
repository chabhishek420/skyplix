<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Application;

class Application extends \Traffic\Service\AbstractService
{
    private $_allowedEnv = ["production", "development", "testing"];
    private $_runningRr = false;
    private $_debug = false;
    private $_cli = false;
    private $_env = NULL;
    private $_revision = NULL;
    const DEFAULT_ENV = "production";
    const PRODUCTION = "production";
    const DEVELOPMENT = "development";
    const TESTING = "testing";
    public function __construct()
    {
        $this->_env = PRODUCTION;
    }
    public function getEnv()
    {
        return $this->_env;
    }
    public function setCli($cli = true)
    {
        $this->_cli = $cli;
    }
    public function setEnv($env)
    {
        if (!in_array($env, $this->_allowedEnv)) {
            throw new Exception\Error("Forbidden environment '" . $env . "'");
        }
        return $this->_env = $env;
    }
    public function setRunningRoadRunner($status = true)
    {
        $this->_runningRr = $status;
    }
    public function setDebug($debug = true)
    {
        $this->_debug = $debug;
    }
    public function setRevision($revision)
    {
        $this->_revision = $revision;
    }
    public function revision()
    {
        return $this->_revision;
    }
    public function isRoadRunnerRunning()
    {
        return $this->_runningRr;
    }
    public function isTesting()
    {
        return $this->getEnv() == TESTING;
    }
    public function isDevelopment()
    {
        return $this->getEnv() == DEVELOPMENT;
    }
    public function isProduction()
    {
        return $this->getEnv() == PRODUCTION;
    }
    public function isDebug()
    {
        return $this->_debug;
    }
    public function isCli()
    {
        return $this->_cli;
    }
    public function getMaxFileSize()
    {
        return min([ini_get("post_max_size"), ini_get("upload_max_filesize")]);
    }
    public function isSlaveModeEnabled()
    {
        return \Traffic\Service\ConfigService::instance()->get("system", "slave_mode", false);
    }
    public function isCompressionEnabled()
    {
        if (!function_exists("gzuncompress") || !function_exists("gzcompress")) {
            return false;
        }
        return !\Traffic\Service\ConfigService::instance()->get("system", "redis_compression_disabled", 0);
    }
}

?>