<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConfigGenerator;

class ConfigGenerator
{
    private $_configSamplePath = NULL;
    private $_envFilePath = NULL;
    private $_prefix = NULL;
    const REDIS_PASSWORD = "REDIS_PASSWORD";
    const SANDBOX_FCGI_PATH = "SANDBOX_FCGI_PATH";
    const SANDBOX_FPM_PATH = "SANDBOX_FPM_PATH";
    public function __construct($configSamplePath, $envFilePath, $prefix = NULL)
    {
        $this->_configSamplePath = $configSamplePath;
        $this->_envFilePath = $envFilePath;
        $this->_prefix = $prefix;
    }
    public function saveTo($path)
    {
        $env = $this->_envFilePath ? \Jsefton\DotEnv\Parser::envToArray($this->_envFilePath) : getenv();
        $content = $this->generateKeitaroConfig($env, $this->_prefix);
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 511, true);
        }
        file_put_contents($path, $content);
    }
    public function generateKeitaroConfig($env, $prefix)
    {
        $content = file_get_contents($this->_configSamplePath);
        $rules = $this->_getRulesFromEnv($env, $prefix);
        foreach ($rules as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        return $content;
    }
    private function _getRulesFromEnv($_getRulesFromEnv, $env, $prefix)
    {
        $redisUri = "";
        if (!empty($env[REDIS_PASSWORD]) || !empty($env[$prefix . REDIS_PASSWORD])) {
            $redisUri .= $this->_require($env, REDIS_PASSWORD, $prefix) . "@";
        }
        $redisUri .= $this->_withDefault($env, "REDIS_HOST", "127.0.0.1", $prefix) . ":" . $this->_withDefault($env, "REDIS_PORT", "6379", $prefix) . "/" . $this->_withDefault($env, "REDIS_DB", "1", $prefix);
        $fcgiPath = "";
        if (!empty($env[SANDBOX_FCGI_PATH]) || !empty($env[$prefix . SANDBOX_FCGI_PATH])) {
            $fcgiPath = $this->_require($env, SANDBOX_FCGI_PATH, $prefix);
        }
        $fpmPath = "";
        if (!empty($env[SANDBOX_FPM_PATH]) || !empty($env[$prefix . SANDBOX_FPM_PATH])) {
            $fpmPath = $this->_require($env, SANDBOX_FPM_PATH, $prefix);
        }
        $sandboxEngine = "";
        if (!empty($env[$prefix . "SANDBOX_ENGINE"])) {
            $sandboxEngine = $this->_require($env, "SANDBOX_ENGINE", $prefix);
        }
        return array_merge($this->_makeRule("uri", $redisUri), $this->_makeRule("port", $this->_withDefault($env, "MARIADB_PORT", "3306", $prefix)), $this->_makeRule("user", $this->_require($env, "MARIADB_USERNAME", $prefix)), $this->_makeRule("server", $this->_withDefault($env, "MARIADB_HOST", "localhost", $prefix)), $this->_makeRule("name", $this->_require($env, "MARIADB_DB", $prefix)), $this->_makeRule("password", $this->_require($env, "MARIADB_PASSWORD", $prefix)), $this->_makeRule("webpack_server_port", $this->_withDefault($env, "WEBPACK_SERVER_PORT", "8080", $prefix)), $this->_makeRule("prefix", "keitaro_"), $this->_makeRule("resolve_method", $this->_require($env, "LICENSE_IP", $prefix)), $this->_makeRule("debug", $this->_withDefault($env, "KEITARO_DEBUG", false, $prefix)), $this->_makeRule("log_level", $this->_withDefault($env, "KEITARO_LOG_LEVEL", "warning", $prefix)), $this->_makeRule("sandbox_fcgi_path", $fcgiPath), $this->_makeRule("sandbox_fpm_path", $fpmPath), $this->_makeRule("sandbox_engine", $sandboxEngine), $this->_makeRule("salt", $this->_withDefault($env, "SALT", uniqid(mt_rand(), true), $prefix)));
    }
    private function _withDefault($env, $key, $default, $prefix = NULL)
    {
        $key = $this->_keyWithPrefix($env, $key, $prefix);
        if (!isset($env[$key]) || is_null($env[$key])) {
            $env[$key] = $default;
        }
        return $env[$key];
    }
    private function _require($env, $key, $prefix = NULL)
    {
        $key = $this->_keyWithPrefix($env, $key, $prefix);
        if (!isset($env[$key]) || is_null($env[$key])) {
            throw new \Exception("ENV param '" . $key . "' must be present in .env file'");
        }
        return $env[$key];
    }
    private function _keyWithPrefix($_keyWithPrefix, $env, $key, $prefix)
    {
        if (isset($env[$prefix . $key])) {
            return $prefix . $key;
        }
        return $key;
    }
    private function _makeRule($_makeRule, $name, $value)
    {
        if (is_string($value) && !is_numeric($value)) {
            $value = "\"" . $value . "\"";
        }
        if (is_bool($value)) {
            $value = $value ? "true" : "false";
        }
        return ["/\\n" . $name . "\\s+=.*\\n/" => "\n" . $name . " = " . $value . "\n"];
    }
}

?>