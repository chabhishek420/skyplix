<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class Grouping
{
    private $_definition = NULL;
    private $_columnNames = NULL;
    public function __construct($columnNames, \Component\Grid\Definition\GridDefinition $definition)
    {
        if (!empty($columnNames) && !is_array($columnNames)) {
            throw new Error("Field 'grouping' must an array");
        }
        if (empty($columnNames)) {
            $columnNames = [];
        }
        $this->_columnNames = $columnNames;
        $this->_definition = $definition;
    }
    public function getColumnNames()
    {
        return $this->_columnNames;
    }
    public function setColumnNames($columnNames)
    {
        $this->_columnNames = $columnNames;
    }
    public function isEmpty()
    {
        return empty($this->_columnNames);
    }
    public function getSql()
    {
        $result = [];
        foreach ($this->_columnNames as $name) {
            $column = $this->_definition->getColumn($name);
            if (!$column->isGroupable()) {
                throw new Error("Field '" . $name . "' can't be grouped");
            }
            $result[] = $column->toGroupSql();
        }
        $result = array_filter($result);
        if (count($result)) {
            return "GROUP BY " . implode(", ", $result);
        }
        return NULL;
    }
}

?>