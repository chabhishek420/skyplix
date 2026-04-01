<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class Sort
{
    private $_rawSorts = NULL;
    private $_reportInfo = NULL;
    public function __construct($rawSorts, \Component\Grid\Definition\GridDefinition $reportInfo)
    {
        if (!empty($rawSorts) && !is_array($rawSorts)) {
            throw new Error("Option 'sort' must an array");
        }
        $this->_rawSorts = $rawSorts;
        $this->_reportInfo = $reportInfo;
    }
    public function getSql()
    {
        if (empty($this->_rawSorts)) {
            return NULL;
        }
        $orders = [];
        foreach ($this->_rawSorts as $rawSort) {
            $field = $this->_reportInfo->getColumn($rawSort["name"]);
            if (!$field->isSortable()) {
                $name = $field->getName();
                throw new Error("Column '" . $name . "' is not sortable");
            }
            $sortItem = new SortItem($field, $rawSort);
            $orders[] = $sortItem->toSql();
        }
        $orders = array_filter($orders);
        if (count($orders)) {
            return "ORDER BY " . implode(", ", $orders);
        }
    }
}

?>