<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class Select
{
    private $_columnNames = NULL;
    private $_definition = NULL;
    private $_summary = NULL;
    public function __construct($columnNames, \Component\Grid\Definition\GridDefinition $definition, $summary = false)
    {
        $this->_columnNames = is_array($columnNames) ? $columnNames : [];
        $this->_definition = $definition;
        $this->_summary = $summary;
    }
    public function getColumnNames()
    {
        return $this->_columnNames;
    }
    public function getInnerSql()
    {
        $selects = [];
        foreach ($this->_columnNames as $columnName) {
            $column = $this->_definition->getColumn($columnName);
            $selects[] = $column->getInnerSelectSql($this->_summary);
        }
        $selects = array_filter($selects);
        if (empty($selects)) {
            throw new Error("Select part is empty");
        }
        return "SELECT " . implode(", ", $selects);
    }
    public function getOuterSql()
    {
        $selects = [];
        foreach ($this->_columnNames as $fieldName) {
            $column = $this->_definition->getColumn($fieldName);
            $selects[] = $column->getOuterSelectSql();
        }
        $selects = array_filter($selects);
        if (!empty($selects)) {
            return "SELECT *, " . implode(", ", $selects);
        }
    }
}

?>