<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Maxmind;

class MaxmindCityAdapter implements \Component\GeoDb\Adapter\GeoDbAdapterInterface
{
    private $_cachedRecords = [];
    private $_client = NULL;
    private $_replaceCityName = ["Moskva" => "Moscow"];
    private $_definition = NULL;
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition)
    {
        $this->_definition = $definition;
    }
    public function info($ip)
    {
        $record = $this->rawInfo($ip);
        return [\Traffic\GeoDb\IpInfoType::COUNTRY => isset($record->country_code) ? $record->country_code : NULL, \Traffic\GeoDb\IpInfoType::REGION => isset($record->region) ? $record->country_code . "_" . $record->region : NULL, \Traffic\GeoDb\IpInfoType::CITY => isset($record->city) ? utf8_encode($this->_convertCityName($record->city)) : NULL];
    }
    public function rawInfo($ip)
    {
        if (!isset($this->_cachedRecords[$ip])) {
            $client = $this->_client();
            $this->_cachedRecords[$ip] = geoip_record_by_addr($client, $ip);
        }
        return $this->_cachedRecords[$ip];
    }
    private function _client()
    {
        if (!isset($this->_client)) {
            require_once ROOT . "/application/Traffic/Vendor/GeoIp.php";
            require_once ROOT . "/application/Traffic/Vendor/GeoIpCity.php";
            if (!file_exists($this->_definition->filePath())) {
                throw new \Component\GeoDb\Error\DbNotFound("GeoDB not found: " . $this->_definition->filePath());
            }
            $this->_client = geoip_open($this->_definition->filePath(), GEOIP_STANDARD);
        }
        return $this->_client;
    }
    private function _convertCityName($name)
    {
        foreach ($this->_replaceCityName as $match => $newName) {
            if ($name == $match) {
                return $newName;
            }
        }
        return $name;
    }
}

?>