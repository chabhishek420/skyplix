<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Migrator7;

class OldStream
{
    protected $_data = [];
    protected $_multiValueFilters = ["langs_filter_value", "countries_filter_value", "regions_filter_value", "cities_filter_value", "browser_filter_value", "os_filter_value", "ip_filter_value", "referer_filter_value", "ua_filter_value", "keywords_filter_value", "operator_filter_value", "device_type_filter_value", "device_model_filter_value"];
    protected $_multiValueFiltersWithSpaces = ["langs_filter_value", "countries_filter_value"];
    protected static $_table = NULL;
    const BLOCK = "block";
    const ALLOW = "allow";
    public static function setTable($table)
    {
        self::$_table = $table;
    }
    public function __construct($data = NULL)
    {
        if (isset($data) && is_array($data)) {
            $this->setData($data);
        }
    }
    public function setData($data)
    {
        $this->_data = array_merge($this->_data, $data);
        return $this;
    }
    public function getId()
    {
        return $this->get("id");
    }
    public function get($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return NULL;
    }
    public function restoreData($data)
    {
        foreach ($data as $field => $value) {
            if (is_string($value) && strstr($value, "&quot;")) {
                $data[$field] = htmlspecialchars_decode($value, ENT_QUOTES);
            }
        }
        $data = $this->_deserialize($data);
        $this->_data = array_merge($this->_data, $data);
    }
    protected function _deserialize($data)
    {
        if (isset($data["manual_filters"]) && !is_array($data["manual_filters"])) {
            $data["manual_filters"] = @unserialize($data["manual_filters"]);
        }
        if (!isset($data["manual_filters"]) || !is_array($data["manual_filters"])) {
            $data["manual_filters"] = [];
        }
        foreach ($this->_multiValueFilters as $filter) {
            if (isset($data[$filter]) && $data[$filter] && !is_array($data[$filter])) {
                if (!strstr($data[$filter], "{")) {
                    if (strstr($data[$filter], ",")) {
                        $data[$filter] = explode(",", $data[$filter]);
                    }
                    if (is_string($data[$filter]) && strstr($data[$filter], "\n")) {
                        $data[$filter] = str_replace("\r", "", $data[$filter]);
                        $data[$filter] = explode("\n", $data[$filter]);
                    }
                    if (is_string($data[$filter]) && in_array($filter, $this->_multiValueFiltersWithSpaces)) {
                        $data[$filter] = explode(" ", $data[$filter]);
                    }
                    if (!empty($data[$filter]) && !is_array($data[$filter])) {
                        $data[$filter] = [$data[$filter]];
                    }
                    if (empty($data[$filter])) {
                        $data[$filter] = NULL;
                    }
                } else {
                    $data[$filter] = (int) @unserialize($data[$filter]);
                }
            }
        }
        return $data;
    }
    protected function _serialize($data)
    {
        if (isset($data["manual_filters"]) && is_array($data["manual_filters"])) {
            foreach ($data["manual_filters"] as $key => $param) {
                $data["manual_filters"][$key]["value"] = $this->_processMultiValue($key, $param["value"]);
            }
        }
        foreach ($this->_multiValueFilters as $filter) {
            if (isset($data[$filter])) {
                $data[$filter] = $this->_processMultiValue($filter, $data[$filter]);
            }
        }
        foreach ($this->_multiValueFilters as $filter) {
            if (isset($data[$filter]) && is_array($data[$filter])) {
                $data[$filter] = serialize($data[$filter]);
            }
        }
        if (isset($data["manual_filters"])) {
            $data["manual_filters"] = serialize($data["manual_filters"]);
        }
        return $data;
    }
    private function _processMultiValue($filter, $value)
    {
        $separators = ["\r\n", "\n"];
        $mainSeparator = ",";
        if ($filter == "countries_filter_value") {
            $separators[] = " ";
        }
        if ($filter == "ua_filter_value") {
            $mainSeparator = "#";
        }
        if (is_array($value)) {
            return $value;
        }
        if (!isset($value) || $value == "") {
            return NULL;
        }
        $value = str_replace($separators, $mainSeparator, $value);
        $values = explode($mainSeparator, $value);
        foreach ($values as $key => $value) {
            $value = trim($value);
            if (empty($value) && $value !== "0") {
                unset($values[$key]);
            } else {
                $values[$key] = $value;
            }
        }
        sort($values);
        return $values;
    }
    public function save()
    {
        $data = $this->_data;
        $data = $this->_serialize($data);
        $this->_data["id"] = $this->_insert($data);
    }
    protected function _insert($data)
    {
        $sql = "INSERT INTO " . \Core\Db\Db::instance()->getPrefix() . self::$_table;
        $fields = array_keys($data);
        $values = array_values($data);
        $fieldsStr = "`" . implode("`, `", $fields) . "`";
        if (isset($value)) {
            $value = \Core\Db\Db::quote($value);
        } else {
            $value = "NULL";
        }
        $sql .= "(" . $fieldsStr . ") VALUES (" . implode(",", $values) . ")";
        \Core\Db\Db::instance()->execute($sql);
        return \Core\Db\Db::instance()->getInsertId();
    }
}

?>