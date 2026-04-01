<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Facebook\Api;

class Proxy
{
    private $_protocol = NULL;
    private $_login = NULL;
    private $_password = NULL;
    private $_address = NULL;
    private $_port = NULL;
    private $_ua = NULL;
    private $_allowedOptions = ["protocol", "login", "password", "address", "port", "ua"];
    const SOCKS5_PROTOCOL = "socks5";
    const SOCKS5H_PROTOCOL = "socks5h";
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
            if (in_array($key, $this->_allowedOptions)) {
                $this->{$name} = $value;
            }
        }
    }
    public function protocol($protocol)
    {
        return $this->_modifyProtocol($this->_protocol);
    }
    public function login()
    {
        return $this->_login;
    }
    public function password()
    {
        return $this->_password;
    }
    public function address()
    {
        return $this->_address;
    }
    public function port()
    {
        return $this->_port;
    }
    public function ua()
    {
        return $this->_ua;
    }
    public function proxyString($proxyString)
    {
        $address = $this->protocol() . "://";
        if ($this->login() !== "") {
            $address .= $this->login() . ":" . $this->password() . "@";
        }
        $address .= $this->address() . ":" . $this->port();
        return $address;
    }
    private function _modifyProtocol($_modifyProtocol, $protocol)
    {
        if ($protocol === SOCKS5_PROTOCOL) {
            return SOCKS5H_PROTOCOL;
        }
        return $protocol;
    }
}

?>