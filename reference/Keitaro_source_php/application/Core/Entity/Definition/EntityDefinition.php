<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Entity\Definition;

class EntityDefinition
{
    private $_className = NULL;
    private $_cacheKey = NULL;
    private $_entityName = NULL;
    private $_aclKey = NULL;
    private $_tableName = NULL;
    private $_primaryKey = NULL;
    private $_reportDefinition = NULL;
    private $_serializer = NULL;
    private $_service = NULL;
    private $_repository = NULL;
    private $_fields = NULL;
    private $_validator = NULL;
    private static $_allowedOptions = ["class_name", "cache_key", "acl_key", "table_name", "primary_key", "report_definition", "serializer", "entity_name", "repository", "service", "fields", "validator"];
    private static $_required = ["class_name", "table_name", "primary_key"];
    public function __construct($options = [])
    {
        foreach ($options as $key => $value) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
            if (!in_array($key, self::$_allowedOptions)) {
                throw new \Exception("Incorrect option " . $key);
            }
            $this->{$name} = $value;
        }
        foreach (self::$_required as $key) {
            if (empty($options[$key])) {
                throw new \Exception("EntityDefinition '" . $options["class_name"] . "' must contain '" . $key . "'");
            }
        }
    }
    public function cacheKey()
    {
        return $this->_cacheKey;
    }
    public function className()
    {
        return $this->_className;
    }
    public function aclKey()
    {
        return $this->_aclKey;
    }
    public function tableName()
    {
        return $this->_tableName;
    }
    public function primaryKey()
    {
        return $this->_primaryKey;
    }
    public function entityName()
    {
        return $this->_entityName;
    }
    public function reportDefinition()
    {
        if (!is_callable($this->_reportDefinition)) {
            throw new \Exception("reportDefinition option must be a function");
        }
        $reportDefinition = call_user_func($this->_reportDefinition);
        if (empty($reportDefinition)) {
            throw new \Exception("reportDefinition is empty for " . $this->_className);
        }
        return $reportDefinition;
    }
    public function serializer()
    {
        $serializer = call_user_func($this->_serializer);
        if (empty($serializer)) {
            throw new \Exception("serializer() is empty for " . $this->_className);
        }
        return $serializer;
    }
    public function repository()
    {
        $repository = call_user_func($this->_repository);
        if (empty($repository)) {
            throw new \Exception("repository is not set for " . $this->_className);
        }
        return $repository;
    }
    public function service()
    {
        $service = call_user_func($this->_service);
        if (empty($service)) {
            throw new \Exception("service is not set for " . $this->_className);
        }
        return $service;
    }
    public function validator()
    {
        return call_user_func($this->_validator);
    }
    public function fields()
    {
        return $this->_fields;
    }
    public function hasField($fieldName)
    {
        return in_array($fieldName, array_keys($this->fields()));
    }
}

?>