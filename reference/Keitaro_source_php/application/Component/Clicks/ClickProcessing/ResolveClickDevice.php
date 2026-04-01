<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class ResolveClickDevice
{
    public function process($entries)
    {
        if (empty($data["ip_string"])) {
            $data["ip_string"] = long2ip($data["ip"]);
        }
        if (!isset($data["is_geo_resolved"]) || !$data["is_geo_resolved"]) {
            $info = \Traffic\Device\Service\IpInfoService::instance()->getIpInfo($data["ip_string"]);
            foreach ($info as $dataType => $value) {
                $data[$dataType] = $value;
            }
            $data["is_isp_resolved"] = 1;
            $data["is_geo_resolved"] = 1;
        }
        if (\Traffic\Repository\CachedSettingsRepository::instance()->get("traffic_log_enabled")) {
            $isIPV4 = filter_var($data["ip_string"], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            if ($isIPV4 && empty($data["country"])) {
                \Traffic\Logging\Service\LoggerService::instance()->info("Failed to resolve country for ip " . $data["ip_string"]);
            }
        }
        if (!isset($data["is_device_resolved"]) || !$data["is_device_resolved"]) {
            $info = \Traffic\Device\Service\DeviceInfoService::instance()->info($data["user_agent"]);
            foreach ($info as $infoType => $value) {
                if ($infoType == \Traffic\Device\DeviceInfoType::IS_BOT && !empty($data[\Traffic\Device\DeviceInfoType::IS_BOT])) {
                    $value = $data[\Traffic\Device\DeviceInfoType::IS_BOT];
                }
                $data[$infoType] = $value;
            }
            $data["is_device_resolved"] = 1;
        }
        return $entries;
    }
}

?>