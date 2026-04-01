<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Ip2Location;

class Ip2LocationAdapter implements \Component\GeoDb\Adapter\GeoDbAdapterInterface
{
    private $_cachedRecords = [];
    private $_definition = NULL;
    private $_client = NULL;
    private $_reverseDic = [];
    private static $_warnShown = false;
    const SUFFIX_PX = "_px";
    const INVALID_VALUE = "-";
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition)
    {
        $this->_definition = $definition;
    }
    public function info($ip)
    {
        $record = $this->rawInfo($ip);
        if ($this->isPxDb()) {
            $info = [\Traffic\GeoDb\IpInfoType::PROXY_TYPE => $this->_wrapInvalidValue(isset($record["countryCode"]) ? $record["countryCode"] : NULL)];
            if (!empty($record["isp"]) && strlen($record["isp"]) == 2) {
                $info[\Traffic\GeoDb\IpInfoType::COUNTRY] = $this->_wrapInvalidIp($record["isp"]);
            }
        } else {
            $info = [\Traffic\GeoDb\IpInfoType::COUNTRY => $this->_wrapInvalidIp(isset($record) ? $record["countryCode"] : NULL), \Traffic\GeoDb\IpInfoType::CITY => $this->_wrapInvalidIp(isset($record["cityName"]) ? utf8_encode($record["cityName"]) : NULL), \Traffic\GeoDb\IpInfoType::ISP => $this->_wrapInvalidIp(isset($record["isp"]) ? utf8_encode($record["isp"]) : NULL)];
            if (!empty($record["countryCode"]) && !empty($record["regionName"])) {
                $info[\Traffic\GeoDb\IpInfoType::REGION] = $this->_wrapInvalidIp($this->_getRegionCode($record["countryCode"], $record["regionName"]));
            }
        }
        return $info;
    }
    private function isPxDb()
    {
        return strstr($this->_definition->id(), SUFFIX_PX);
    }
    private function _wrapInvalidValue($value)
    {
        if ($value === INVALID_VALUE) {
            return NULL;
        }
        return $value;
    }
    private function _wrapInvalidIp($val)
    {
        if ($val == \IP2Location\Database::FIELD_NOT_SUPPORTED) {
            return NULL;
        }
        if ($val == \IP2Location\Database::INVALID_IP_ADDRESS) {
            if (!extension_loaded("bcmath") && !self::$_warnShown) {
                self::$_warnShown = true;
                $message = "Incorrect IPv6 geo detection. Install  bcmath extension";
                \Traffic\Logging\Service\LoggerService::instance()->error($message);
                if (!\Core\Application\Application::instance()->isProduction()) {
                    throw new \Exception($message);
                }
            }
            return "";
        }
        return $val;
    }
    private function _getRegionCode($country, $regionName)
    {
        if (!isset($this->_reverseDic[$country])) {
            $fileName = ROOT . "/application/Traffic/GeoDb/ip2location_reverse/" . $country . ".php";
            if (!is_file($fileName)) {
                return NULL;
            }
            $this->_reverseDic[$country] = (include $fileName);
        }
        if (isset($this->_reverseDic[$country][$regionName])) {
            return $country . "_" . $this->_reverseDic[$country][$regionName];
        }
        return NULL;
    }
    public function rawInfo($ip)
    {
        return $this->_client()->lookup($ip, \IP2Location\Database::ALL);
    }
    private function _client()
    {
        if (empty($this->_client)) {
            if (!is_file($this->_definition->filePath())) {
                throw new \Component\GeoDb\Error\DbNotFound("GeoDB file not found " . $this->_definition->filePath());
            }
            $this->_client = new \IP2Location\Database($this->_definition->filePath(), \IP2Location\Database::FILE_IO);
        }
        return $this->_client;
    }
}

?>