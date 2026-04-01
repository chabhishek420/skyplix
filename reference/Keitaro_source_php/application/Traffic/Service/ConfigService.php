<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Service;

class ConfigService extends AbstractService
{
    private $_cnf = NULL;
    const SANDBOX_ENGINE = "sandbox_engine";
    const SANDBOX_FPM_PATH = "sandbox_fpm_path";
    const SANDBOX_FCGI_PATH = "sandbox_fcgi_path";
    public function init($path)
    {
        $text = file_get_contents($path);
        $this->_cnf = parse_ini_string($text, true);
    }
    public function findConfigPath()
    {
        if (\Core\Application\Application::instance()->isTesting()) {
            $cnfFile = ROOT . "/application/config/config.ini.php";
            $legacyCnfFile = ROOT . "/application/config/config.ini";
            if (!file_exists($cnfFile) && file_exists($legacyCnfFile)) {
                $cnfFile = $legacyCnfFile;
            }
        } else {
            $cnfFile = ROOT . "/application/config/config.testing.ini.php";
        }
        return $cnfFile;
    }
    public function set($scope, $key, $value)
    {
        $this->_cnf[$scope][$key] = $value;
    }
    public function get($scope, $key = NULL, $default = NULL)
    {
        if ($this->has($scope, $key)) {
            if (isset($key)) {
                return $this->_cnf[$scope][$key];
            }
            return $this->_cnf[$scope];
        }
        if (isset($default)) {
            return $default;
        }
        return NULL;
    }
    public function delete($scope, $key)
    {
        unset($this->_cnf[$scope][$key]);
    }
    public function has($scope, $key = NULL)
    {
        return isset($this->_cnf[$scope]) && (!isset($key) || isset($this->_cnf[$scope][$key]));
    }
    public function getDebugValue()
    {
        return (int) ConfigService::instance()->get("system", "debug") && file_exists(ROOT . "/DEVELOP");
    }
    public function isDemo()
    {
        return (int) ConfigService::instance()->get("system", "demo");
    }
    public function isReferrerRedefineAllowed()
    {
        return (int) ConfigService::instance()->get("system", "allow_change_referrer", false);
    }
}

?>