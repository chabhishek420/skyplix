<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\ProIP;

class ProIPAdapter implements \Component\GeoDb\Adapter\GeoDbAdapterInterface
{
    private $_definition = NULL;
    private $_client = NULL;
    private $_reverseDic = [];
    private static $_warnShown = false;
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition)
    {
        $this->_definition = $definition;
    }
    public function info($ip)
    {
        $record = $this->rawInfo($ip);
        $info = [\Traffic\GeoDb\IpInfoType::COUNTRY => $this->_wrapInvalidIp(isset($record) ? $record->countryCode : NULL), \Traffic\GeoDb\IpInfoType::CITY => $this->_wrapInvalidIp(isset($record->city) ? utf8_encode($record->city) : NULL), \Traffic\GeoDb\IpInfoType::ISP => $this->_wrapInvalidIp(isset($record->ISP) ? utf8_encode($record->ISP) : NULL)];
        if (!empty($record->countryCode) && !empty($record->region)) {
            $info[\Traffic\GeoDb\IpInfoType::REGION] = $this->_wrapInvalidIp($this->_getRegionCode($record->countryCode, $record->region));
        }
        return $info;
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
        return $this->_client()->getRecord($ip);
    }
    private function _client()
    {
        if (empty($this->_client)) {
            if (!is_file($this->_definition->filePath())) {
                throw new \Component\GeoDb\Error\DbNotFound("GeoDB file not found " . $this->_definition->filePath());
            }
            $stream = new \Core\KeitaroDb\Common\DbStream($this->_definition->filePath(), true);
            $this->_client = new \Core\KeitaroDb\GDBC\GDBCClient($stream);
        }
        return $this->_client;
    }
}

?>