<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\AppsFlyer;

class AppsFlyerIntegrationSettings
{
    private $_id = NULL;
    private $_integration = NULL;
    private $_name = NULL;
    private $_appName = NULL;
    private $_apiToken = NULL;
    private $_lastError = NULL;
    private $_status = NULL;
    private $_lastUpdate = NULL;
    private $_updateTime = NULL;
    private $_allowedOptions = ["integration", "proxy_enabled", "name", "app_name", "api_token", "proxy", "last_error", "status", "last_update", "update_time"];
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
            if (in_array($key, $this->_allowedOptions)) {
                $this->{$name} = $value;
            }
        }
    }
    public function getIntegration()
    {
        return $this->_integration;
    }
    public function getName()
    {
        return $this->_name;
    }
    public function getAppName()
    {
        return $this->_appName;
    }
    public function getApiToken()
    {
        return $this->_apiToken;
    }
    public function getLastError()
    {
        return $this->_lastError;
    }
    public function getStatus()
    {
        return $this->_status;
    }
    public function getLastUpdate()
    {
        return $this->_lastUpdate;
    }
    public function setId($id = NULL)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }
    public function getUpdateTime()
    {
        if ($this->_updateTime) {
            return $this->_updateTime;
        }
        return 180;
    }
}

?>