<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Migrator7;

class TdsMigrator7
{
    public static function getKeitaro6Fields()
    {
        return ["countries_filter", "countries_filter_value", "cities_filter", "cities_filter_value", "ip_filter", "ip_filter_value", "browser_filter", "browser_filter_value", "os_filter", "os_filter_value", "keywords_filter_value", "langs_filter", "langs_filter_value", "referer_filter", "referer_filter_value", "proxy_filter", "schedule", "start_date", "end_date", "manual_filters", "ua_filter", "ua_filter_value", "mobile_filter", "operator_filter", "operator_filter_value", "unique_filter", "unique_filter_scope", "regions_filter", "regions_filter_value", "device_type_filter", "device_type_filter_value", "device_model_filter", "device_model_filter_value", "unique_filter_storage", "checker_url", "checker_regx", "checker_action", "checker_interval", "checker_source", "checker_action_done"];
    }
    public function run($data)
    {
        $data = $this->_migrateUniqueness($data);
        $data = $this->_migrateMulti($data, "country", "countries");
        $data = $this->_migrateMulti($data, "city", "cities");
        $data = $this->_migrateMulti($data, "region", "regions");
        $data = $this->_migrateMulti($data, "language", "langs");
        $data = $this->_migrateMulti($data, "browser", "browser");
        $data = $this->_migrateMulti($data, "os", "os");
        $data = $this->_migrateMulti($data, "referrer", "referer", false);
        $data = $this->_migrateMulti($data, "keyword", "keywords", false);
        $data = $this->_migrateMulti($data, "device_model", "device_model");
        $data = $this->_migrateMulti($data, "device_type", "device_type");
        $data = $this->_migrateMulti($data, "operator", "operator");
        $data = $this->_migrateMulti($data, "user_agent", "ua");
        $data = $this->_migrateMulti($data, "ip", "ip");
        $data = $this->_migrateInterval($data);
        $data = $this->_migrateSchedule($data);
        $data = $this->_migrateParameters($data);
        return $data;
    }
    protected function _getMode($oldMode)
    {
        $modes = ["allow" => "accept", "block" => "reject"];
        if (!isset($modes[$oldMode])) {
            return "accept";
        }
        return $modes[$oldMode];
    }
    protected function _migrateUniqueness($data)
    {
        if (is_object($data)) {
            throw new \Core\Application\Exception\Error("stream must be array");
        }
        $value = $data["unique_filter"];
        if (!empty($value)) {
            $filter = ["name" => "uniqueness_" . ($data["unique_filter_storage"] == "ip" ? "ip" : "cookie"), "mode" => "accept", "payload" => str_replace("enable_", "", $data["unique_filter_scope"])];
            $data = $this->_addFilter($data, $filter, true);
        }
        return $data;
    }
    protected function _migrateMulti($data, $filterName, $prefix, $withMode = true)
    {
        $mode = $data[$prefix . "_filter"];
        $value = $data[$prefix . "_filter_value"];
        if ($withMode && !empty($mode) || !$withMode && !empty($value)) {
            $payload = $data[$prefix . "_filter_value"];
            if ($prefix == "device_type") {
                $payload = $this->_convertDeviceTypes($payload);
            }
            if ($prefix == "os") {
                $payload = $this->_convertOs($payload);
            }
            $filter = ["name" => $filterName, "payload" => $payload];
            if ($withMode) {
                $filter["mode"] = $this->_getMode($data[$prefix . "_filter"]);
            } else {
                $filter["mode"] = "accept";
            }
            $data = $this->_addFilter($data, $filter, true);
        }
        return $data;
    }
    protected function _migrateInterval($data)
    {
        $startDate = $data["start_date"];
        $endDate = $data["end_date"];
        $payload = [];
        if ($startDate) {
            $payload["from"] = date("Y-m-d", $startDate);
        }
        if ($endDate) {
            $payload["to"] = date("Y-m-d", $endDate);
        }
        if (count($payload)) {
            $data = $this->_addFilter($data, ["name" => "interval", "mode" => "accept", "payload" => $payload]);
        }
        return $data;
    }
    protected function _migrateSchedule($data)
    {
        $schedule = $data["schedule"];
        if (!$schedule) {
            return $data;
        }
        $payload = [];
        $tmp = explode("|", $schedule);
        $hours = explode("-", $tmp[0]);
        $days = explode("-", $tmp[1]);
        $days = array_keys($days, "1");
        $interval = [];
        $payload = [];
        $intervals = [];
        foreach ($hours as $hour => $status) {
            if ($status) {
                if (!count($interval)) {
                    $interval[0] = str_pad($hour, 2, "0", STR_PAD_LEFT) . ":00";
                }
                $interval[1] = str_pad($hour + 1, 2, "0", STR_PAD_LEFT) . ":00";
            } else {
                if (count($interval)) {
                    $intervals[] = $interval;
                    $interval = [];
                }
            }
        }
        foreach ($days as $day) {
            foreach ($intervals as $interval) {
                $payload[] = ["day" => $day, "time" => $interval];
            }
        }
        if (count($payload)) {
            $data = $this->_addFilter($data, ["name" => "schedule", "mode" => "accept", "payload" => $payload]);
        }
        return $data;
    }
    protected function _migrateParameters($data)
    {
        $params = $data["manual_filters"];
        if (empty($params)) {
            return $data;
        }
        $params = unserialize($params);
        foreach ($params as $name => $filter) {
            $mode = $this->_getMode($filter["select"]);
            $payload = ["name" => $name, "value" => is_array($filter["value"]) ? $filter["value"] : [$filter["value"]]];
            $data = $this->_addFilter($data, ["name" => "parameter", "mode" => $mode, "payload" => $payload]);
        }
        return $data;
    }
    protected function _addFilter($data, $filter, $multiValue = false)
    {
        if (empty($data["filters"])) {
            $data["filters"] = [];
        }
        if (isset($filter["payload"]) && is_string($filter["payload"]) && strpos($filter["payload"], "a:") === 0) {
            $filter["payload"] = unserialize($filter["payload"]);
        }
        if ($multiValue && !is_array($filter["payload"])) {
            $filter["payload"] = [$filter["payload"]];
        }
        $data["filters"][] = $filter;
        return $data;
    }
    private function _convertDeviceTypes($values)
    {
        $data = ["gaming" => "console", "debt" => "desktop", "reader" => "", "media" => "portable_media_player", "headset" => "", "emulator" => "", "television" => "tv", "monitor" => "smart_display", "signage" => "", "whiteboard" => "smart_display", "gps" => "", "car" => "car_browser", "pos" => "", "bot" => ""];
        $result = [];
        if (is_array($values)) {
            foreach ($values as $value) {
                if (isset($data[$value])) {
                    $result[] = $data[$value];
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }
    private function _convertOs($values)
    {
        $data = ["Mobile Internet Explorer" => "IE Mobile", "System60" => "Symbian OS", "PSP" => "PS Portable", "WiiU" => "Nintendo WiiU"];
        $result = [];
        if (is_array($values)) {
            foreach ($values as $value) {
                if (isset($data[$value])) {
                    $result[] = $data[$value];
                } else {
                    $result[] = $value;
                }
            }
        }
        return $result;
    }
}

?>