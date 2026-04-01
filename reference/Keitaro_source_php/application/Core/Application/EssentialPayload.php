<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Application;

class EssentialPayload
{
    private $_ip = NULL;
    private $_hashExpired = NULL;
    private $_licenseExpired = NULL;
    private $_key = NULL;
    private $_edition = NULL;
    private $_limitUsers = NULL;
    private $_limitClickApi = NULL;
    private $_adminApiBusinessOnly = NULL;
    private $_error = NULL;
    private $_errorMessage = NULL;
    private $_signature = NULL;
    private $_allowedOptions = ["ip", "hash_expired", "license_expired", "key", "edition", "limit_users", "limit_click_api", "admin_api_business_only", "error_message", "error", "signature"];
    private $_ignoreOptions = ["success", "state", "lockable"];
    const PRO = "pro";
    const BUSINESS = "business";
    const TRIAL = "trial";
    const BASIC = "basic";
    const NO_LICENSE = "no_license";
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            if (!in_array($key, $this->_ignoreOptions)) {
                $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
                if (in_array($key, $this->_allowedOptions)) {
                    $this->{$name} = $value;
                } else {
                    if (Application::instance()->isDebug()) {
                        throw new \Exception("Incorrect field \"" . $key . "\"");
                    }
                }
            }
        }
    }
    public function isLicenseExpired($now = NULL)
    {
        if (empty($now)) {
            $now = time();
        }
        return $this->licenseExpired() < $now;
    }
    public function isCorrect()
    {
        return $this->_key && $this->_ip && $this->_licenseExpired && $this->_hashExpired;
    }
    public function isTokenExpired($now = NULL, $extraTime = 0)
    {
        if (empty($now)) {
            $now = time();
        }
        return !$this->tokenExpiresAt() || $this->tokenExpiresAt() + $extraTime < $now;
    }
    public function ip()
    {
        return $this->_ip;
    }
    public function tokenExpiresAt()
    {
        return $this->_hashExpired;
    }
    public function licenseExpired()
    {
        return $this->_licenseExpired;
    }
    public function key()
    {
        return $this->_key;
    }
    public function edition()
    {
        return $this->_edition;
    }
    public function errorMessage()
    {
        return $this->_errorMessage;
    }
    public function error()
    {
        return $this->_error;
    }
    public function mustLimitUsers()
    {
        return $this->_limitUsers === true || $this->_limitUsers === 1;
    }
    public function clickApiForProOnly()
    {
        return $this->_limitClickApi === true || $this->_limitClickApi === 1;
    }
    public function adminApiBusinessOnly()
    {
        return $this->_adminApiBusinessOnly === true || $this->_adminApiBusinessOnly === 1;
    }
}

?>