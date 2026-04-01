<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Maxmind;

class MaxmindCountryAdapter implements \Component\GeoDb\Adapter\GeoDbAdapterInterface
{
    private $_client = NULL;
    private $_definition = NULL;
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition)
    {
        $this->_definition = $definition;
    }
    public function info($ip)
    {
        return [\Traffic\GeoDb\IpInfoType::COUNTRY => $this->rawInfo($ip)];
    }
    public function rawInfo($ip)
    {
        $client = $this->_client();
        return geoip_country_code_by_addr($client, $ip);
    }
    private function _client()
    {
        if (empty($this->_client)) {
            require_once ROOT . "/application/Traffic/Vendor/GeoIp.php";
            require_once ROOT . "/application/Traffic/Vendor/GeoIpCity.php";
            if (!file_exists($this->_definition->filePath())) {
                throw new \Component\GeoDb\Error\DbNotFound("Database not found " . $this->_definition->filePath());
            }
            $this->_client = geoip_open($this->_definition->filePath(), GEOIP_COUNTRY_EDITION);
        }
        return $this->_client;
    }
}

?>