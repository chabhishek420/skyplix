<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Definition;

class Relation
{
    private $_definition = NULL;
    private $_name = NULL;
    private $_primaryKey = "id";
    private $_foreignKey = NULL;
    private $_class = NULL;
    private $_allowEmpty = NULL;
    private $_dict = NULL;
    private $_tableName = NULL;
    private $_through = NULL;
    private $_validOptions = ["primary_key", "name", "class", "dict", "through", "allow_empty", "foreign_key", "definition"];
    private $_required = ["class", "foreign_key", "name"];
    public function __construct($opts)
    {
        foreach ($opts as $opt => $value) {
            if (!in_array($opt, $this->_validOptions)) {
                throw new Error("Incorrect relation option '" . $opt . "'");
            }
            $name = "_" . lcfirst(\Traffic\Tools\Tools::toCamelCase($opt));
            $this->{$name} = $value;
        }
        foreach ($this->_required as $opt) {
            if (empty($opts[$opt])) {
                throw new Error("Error in relation '" . $this->_name . "'. Option '" . $opt . "' is required.");
            }
        }
        $class = $this->_class;
        $this->_tableName = $class::getTableName();
    }
    public function setDefinition(GridDefinition $definition)
    {
        $this->_definition = $definition;
    }
    public function getName()
    {
        return $this->_name;
    }
    public function getClass()
    {
        return $this->_class;
    }
    public function getTableName()
    {
        return $this->_tableName;
    }
    public function allowEmpty()
    {
        return $this->_allowEmpty;
    }
    public function getPrimaryKey()
    {
        return $this->_primaryKey;
    }
    public function getForeignKey()
    {
        return $this->_foreignKey;
    }
    public function isDict()
    {
        return $this->_dict;
    }
    public function getThrough()
    {
        return $this->_through;
    }
    public function getJoins()
    {
        if ($this->getThrough()) {
            $relation = $this->_definition->getRelation($this->getThrough());
            $joins = $relation->getJoins();
        } else {
            $joins = [];
        }
        if ($this->getThrough()) {
            $parentTable = "t_" . $this->getThrough();
        } else {
            $table = $this->_definition->getTable();
            $parentTable = "t_" . $table;
        }
        $tableName = $this->getTableName();
        $name = $this->getName();
        $foreignKey = $this->getForeignKey();
        $primaryKey = $this->getPrimaryKey();
        $joins[$this->getName()] = "LEFT JOIN " . $tableName . " AS t_" . $name . " ON t_" . $name . "." . $primaryKey . " = " . $parentTable . "." . $foreignKey;
        return $joins;
    }
    public function getGroupableColumn()
    {
        $primaryKey = $this->getPrimaryKey();
        $name = $this->getName();
        return "t_" . $name . "." . $primaryKey;
    }
    public function isSubId()
    {
        return strstr($this->getName(), "sub_id");
    }
    public function getFilterableColumn($columnType)
    {
        $primaryKey = $this->getPrimaryKey();
        $name = $this->getName();
        $foreignKey = $this->getForeignKey();
        if ($columnType == Column::INTEGER) {
            return "t_" . $name . "." . $primaryKey;
        }
        $table = $this->_definition->getTable();
        return "t_" . $table . "." . $foreignKey;
    }
}

?>