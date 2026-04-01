<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Facebook;

class FbIntegrationSettings
{
    private $_id = NULL;
    private $_integration = NULL;
    private $_proxyEnabled = NULL;
    private $_name = NULL;
    private $_adAccountId = NULL;
    private $_token = NULL;
    private $_proxy = NULL;
    private $_lastError = NULL;
    private $_status = NULL;
    private $_lastUpdate = NULL;
    private $_interval = NULL;
    private $_campaigns = [];
    private $_allowedOptions = ["integration", "proxy_enabled", "name", "ad_account_id", "token", "proxy", "last_error", "status", "last_update", "interval"];
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
    public function getProxyEnabled()
    {
        return $this->_proxyEnabled;
    }
    public function getName()
    {
        return $this->_name;
    }
    public function getAdAccountId()
    {
        return $this->_adAccountId;
    }
    public function getToken()
    {
        return $this->_token;
    }
    public function getProxy()
    {
        return $this->_proxy;
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
    public function getInterval()
    {
        $this->_interval ? exit : 72;
    }
    public function setId($id = NULL)
    {
        $this->_id = $id;
    }
    public function getId()
    {
        return $this->_id;
    }
    public function setCampaigns($id = NULL)
    {
        $this->_campaigns[] = $id;
    }
    public function getCampaigns()
    {
        return $this->_campaigns;
    }
}

?>