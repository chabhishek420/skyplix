<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb;

class GeoDbDefinition
{
    private $_name = NULL;
    private $_id = NULL;
    private $_type = NULL;
    private $_dataTypes = NULL;
    private $_filePath = NULL;
    private $_dictionariesPath = NULL;
    private $_isRecommended = NULL;
    private $_settingKey = NULL;
    private $_purchaseLink = NULL;
    private $_allowedOptions = ["id", "name", "type", "data_types", "file_path", "dictionaries_path", "is_recommended", "setting_key", "purchase_link"];
    private $_required = ["id", "file_path", "type"];
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
            if (!in_array($key, $this->_allowedOptions)) {
                throw new \Exception("Incorrect option " . $key);
            }
            $this->{$name} = $value;
        }
        if (empty($this->_name)) {
            $this->_name = $this->_id;
        }
        foreach ($this->_required as $option) {
            if (empty($options[$option])) {
                throw new \Exception("DB " . $this->id() . " must contain '" . $option . "' option");
            }
        }
    }
    public function id()
    {
        return $this->_id;
    }
    public function name()
    {
        return $this->_name;
    }
    public function dataTypes()
    {
        if (empty($this->_dataTypes)) {
            throw new \Exception("Empty dataTypes for " . $this->id());
        }
        return $this->_dataTypes;
    }
    public function type()
    {
        return $this->_type;
    }
    public function filePath()
    {
        if (is_callable($this->_filePath)) {
            return call_user_func($this->_filePath);
        }
        return $this->_filePath;
    }
    public function setFilePath($path)
    {
        return $this->_filePath = $path;
    }
    public function dictionariesPath()
    {
        return $this->_dictionariesPath;
    }
    public function isRecommended()
    {
        return $this->_isRecommended;
    }
    public function settingKey()
    {
        return $this->_settingKey;
    }
    public function purchaseLink()
    {
        return $this->_purchaseLink;
    }
}

?>