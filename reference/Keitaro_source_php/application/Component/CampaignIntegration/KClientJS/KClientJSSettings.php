<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\CampaignIntegration\KClientJS;

class KClientJSSettings
{
    private $_campaignId = NULL;
    private $_unique = true;
    private $_url = "";
    private $_host = "";
    private $_base = true;
    private $_allowedOptions = ["campaign_id", "unique", "url", "host", "base"];
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
            if (in_array($key, $this->_allowedOptions)) {
                $this->{$name} = $value;
            }
        }
    }
    public function getUnique()
    {
        return $this->_unique ? "true" : "false";
    }
    public function getCampaignId()
    {
        return $this->_campaignId;
    }
    public function getUrl()
    {
        return $this->_url;
    }
    public function getHost()
    {
        return $this->_host;
    }
    public function getBase()
    {
        return $this->_base;
    }
    public function getCookiesTTL()
    {
        if ($this->_campaignId) {
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($this->_campaignId);
            return $campaign->get("cookies_ttl") * 60 * 60;
        }
        return 86400;
    }
}

?>