<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Device\Service;

class DeviceInfoService extends \Traffic\Service\AbstractService
{
    private $_exceptManufactures = ["Apple", "RIM"];
    private $_detector = NULL;
    private $_matches = NULL;
    private $_deviceTypeReplacements = ["smartphone" => "mobile", "feature_phone" => "mobile", "phablet" => "mobile"];
    private $_osReplacements = ["Mac" => "OS X", "MTK / Nucleus" => "MTK", "PlayStation Portable" => "PS", "PS Portable" => "PS", "Nintendo Mobile" => "Nintendo"];
    public function getExceptManufactures()
    {
        return $this->_exceptManufactures;
    }
    public function info($ua)
    {
        \DeviceDetector\Parser\Device\AbstractDeviceParser::setVersionTruncation(\DeviceDetector\Parser\Device\AbstractDeviceParser::VERSION_TRUNCATION_NONE);
        $toCheckBot = \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::CHECK_BOT_UA);
        $this->_init();
        $this->_detector->skipBotDetection(!$toCheckBot);
        $this->_detector->setUserAgent($ua);
        try {
            $this->_detector->parse();
        } catch (\Exception $e) {
            \Traffic\Logging\Service\LoggerService::instance()->warning($e->getMessage());
            $browser = $this->_detector->getClient();
            $os = $this->_detector->getOs();
            $data = [\Traffic\Device\DeviceInfoType::BROWSER => isset($browser["name"]) ? $browser["name"] : NULL, \Traffic\Device\DeviceInfoType::BROWSER_VERSION => isset($browser["version"]) ? $browser["version"] : NULL, \Traffic\Device\DeviceInfoType::OS => $this->_convertOs(isset($os["name"]) ? $os["name"] : NULL), \Traffic\Device\DeviceInfoType::OS_VERSION => isset($os["version"]) ? $os["version"] : NULL, \Traffic\Device\DeviceInfoType::DEVICE_TYPE => $this->_convertDeviceType($this->_detector), \Traffic\Device\DeviceInfoType::DEVICE_BRAND => $this->_detector->getBrandName(), \Traffic\Device\DeviceInfoType::DEVICE_MODEL => $this->_convertDeviceModel($this->_detector->getBrandName(), $this->_detector->getModel())];
            if ($toCheckBot) {
                $data[\Traffic\Device\DeviceInfoType::IS_BOT] = $this->_detector->isBot();
            }
            return $data;
        }
    }
    private function _convertDeviceModel($deviceBrand, $deviceModel)
    {
        $name = "";
        if (!empty($deviceBrand)) {
            if (!in_array($deviceBrand, $this->_exceptManufactures)) {
                $name = $deviceBrand;
            }
            if (isset($deviceModel)) {
                if ($name) {
                    $name .= " ";
                }
                $name .= $deviceModel;
            }
        }
        return $name;
    }
    private function _convertOs($os)
    {
        if (isset($this->_osReplacements[$os])) {
            return $this->_osReplacements[$os];
        }
        return $os;
    }
    protected function _convertDeviceType(\DeviceDetector\DeviceDetector $dd)
    {
        $deviceType = NULL;
        $id = $dd->getDevice();
        if ($id == \DeviceDetector\Parser\Device\AbstractDeviceParser::DEVICE_TYPE_PHABLET) {
            $id = \DeviceDetector\Parser\Device\AbstractDeviceParser::DEVICE_TYPE_SMARTPHONE;
        }
        if ($id == \DeviceDetector\Parser\Device\AbstractDeviceParser::DEVICE_TYPE_FEATURE_PHONE) {
            $id = \DeviceDetector\Parser\Device\AbstractDeviceParser::DEVICE_TYPE_SMARTPHONE;
        }
        if ($id !== NULL) {
            $deviceType = array_search($id, $this->_matches);
        }
        if (empty($deviceType)) {
            if (strstr($dd->getOs("name"), "Android") || stristr($dd->getOs("name"), "Phone") || $dd->getOs("name") == "Windows CE" || $dd->getOs("name") == "Symbian OS") {
                $deviceType = \Traffic\Device\DeviceType::DEVICE_MOBILE;
            }
            if (strstr($dd->getClient("name"), "Mobile") || strstr($dd->getClient("name"), "Mini")) {
                $deviceType = \Traffic\Device\DeviceType::DEVICE_MOBILE;
            }
        }
        if (isset($this->_deviceTypeReplacements[$deviceType])) {
            return $this->_deviceTypeReplacements[$deviceType];
        }
        return $deviceType;
    }
    private function _init()
    {
        if (!isset($this->_detector)) {
            $this->_detector = new \DeviceDetector\DeviceDetector();
            $cache = new \Traffic\Device\Cache\DeviceCacheAdapter(\Traffic\Cache\CacheService::instance()->deviceDetectorCache()->getDoctrineCache());
            $this->_detector->setCache($cache);
            $this->_detector->discardBotInformation();
        }
    }
}

?>