<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Sypex;

class SypexCityAdapter implements \Component\GeoDb\Adapter\GeoDbAdapterInterface
{
    private $_cachedRecords = [];
    private $_client = NULL;
    private $_definition = NULL;
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition)
    {
        $this->_definition = $definition;
    }
    public function info($ip)
    {
        $record = $this->rawInfo($ip);
        $record["country"]["iso"] ? exit : NULL;
    }
    public function rawInfo($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return NULL;
        }
        if (!isset($this->_cachedRecords[$ip])) {
            $this->_cachedRecords[$ip] = $this->_client()->getCityFull($ip);
        }
        return $this->_cachedRecords[$ip];
    }
    private function _client()
    {
        if (!isset($this->_client)) {
            if (!file_exists($this->_definition->filePath())) {
                throw new \Component\GeoDb\Error\DbNotFound("GeoDB not found " . $this->_definition->filePath());
            }
            $this->_client = new \Traffic\Vendor\SxGeo($this->_definition->filePath());
        }
        return $this->_client;
    }
    private function _convertRegion($region)
    {
        return str_replace("-", "_", $region);
    }
}

?>