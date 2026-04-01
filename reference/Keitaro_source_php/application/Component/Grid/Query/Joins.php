<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class Joins
{
    private $_columns = NULL;
    private $_tableDefinition = NULL;
    public function __construct($fields, \Component\Grid\Definition\GridDefinition $definition)
    {
        $this->_columns = is_array($fields) ? $fields : [];
        $this->_tableDefinition = $definition;
    }
    public function getSql()
    {
        $joins = [];
        foreach ($this->_columns as $columnName) {
            $column = $this->_tableDefinition->getColumn($columnName);
            $joins = array_merge($joins, $column->toJoins());
        }
        return implode(" ", array_values($joins));
    }
}

?>