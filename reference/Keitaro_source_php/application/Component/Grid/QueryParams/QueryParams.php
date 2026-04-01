<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\QueryParams;

class QueryParams
{
    private $_requiredColumns = NULL;
    private $_filters = NULL;
    private $_sort = NULL;
    private $_range = NULL;
    private $_limit = NULL;
    private $_offset = NULL;
    private $_columns = [];
    private $_grouping = [];
    private $_metrics = [];
    private $_summary = NULL;
    private $_format = NULL;
    private $_definition = NULL;
    private $_params = NULL;
    private $_required = [];
    private $_array = NULL;
    private $_multiDimensional = NULL;
    private $_rawParams = NULL;
    const GROUPING = "grouping";
    const METRICS = "metrics";
    const SORT = "sort";
    const COLUMNS = "columns";
    const LIMIT = "limit";
    const OFFSET = "offset";
    const FILTERS = "filters";
    const RANGE = "range";
    const SUMMARY = "summary";
    const FORMAT = "format";
    public function __construct($rawParams, \Component\Grid\Definition\GridDefinition $definition)
    {
        if (!empty($rawParams["dimensions"])) {
            $rawParams["grouping"] = $rawParams["dimensions"];
            unset($rawParams["dimensions"]);
        }
        $this->_rawParams = $rawParams;
        $this->_definition = $definition;
        foreach ($this->_params as $param) {
            if (!empty($rawParams[$param])) {
                $name = "_" . lcfirst(\Traffic\Tools\Tools::toCamelCase($param));
                $this->{$name} = $rawParams[$param];
            }
        }
        $this->_checkColumns();
        $this->_checkRequiredColumns();
        $this->_validate($rawParams);
    }
    private function _validate($rawParams)
    {
        $this->_validateTypes($rawParams);
        $this->_checkGrouping($rawParams);
        $this->_checkSorting();
        $this->_checkLimit($rawParams);
        $this->_checkOffset($rawParams);
        $this->_checkRange($rawParams);
        $this->_checkFilterByIp();
    }
    public function getDefinition()
    {
        return $this->_definition;
    }
    public function getColumns()
    {
        return $this->_columns;
    }
    public function getRequiredColumns()
    {
        return $this->_requiredColumns;
    }
    public function getFilters()
    {
        return $this->_filters;
    }
    public function getSort()
    {
        return $this->_sort;
    }
    public function getRange()
    {
        return $this->_range;
    }
    public function getLimit()
    {
        return $this->_limit;
    }
    public function getOffset()
    {
        return $this->_offset;
    }
    public function getGrouping()
    {
        return $this->_grouping;
    }
    public function getFormat()
    {
        return $this->_format;
    }
    public function summary()
    {
        return $this->_summary;
    }
    private function _validateTypes($rawParams)
    {
        foreach ($this->_array as $paramName) {
            if (!empty($rawParams[$paramName]) && !is_array($rawParams[$paramName])) {
                throw new Error("Param '" . $paramName . "' must be an array");
            }
        }
        foreach ($this->_multiDimensional as $paramName) {
            if (!empty($rawParams[$paramName]) && (!is_array($rawParams[$paramName]) || !is_array(array_pop($rawParams[$paramName])))) {
                throw new Error("Param '" . $paramName . "' must be an array of objects");
            }
        }
        foreach ($this->_required as $paramName) {
            if (empty($rawParams[$paramName])) {
                throw new Error("Param '" . $paramName . "' is required");
            }
        }
    }
    private function _checkGrouping($rawParams)
    {
        if (isset($rawParams[GROUPING])) {
            foreach ($rawParams[GROUPING] as $groupField) {
                if (!in_array($groupField, $this->_columns)) {
                    throw new Error("Grouping column '" . $groupField . "' must be included in 'columns' list");
                }
            }
        }
    }
    private function _checkSorting()
    {
        if (!empty($this->_sort)) {
            foreach ($this->_sort as $num => $sortField) {
                if (!in_array($sortField["name"], $this->_columns)) {
                    unset($this->_sort[$num]);
                }
            }
        }
    }
    private function _checkLimit($rawParams)
    {
        if (!empty($rawParams[LIMIT]) && $rawParams[LIMIT] < 0) {
            throw new Error("Limit must be greater than 0");
        }
    }
    private function _checkOffset($rawParams)
    {
        if (!empty($rawParams[OFFSET]) && $rawParams[OFFSET] < 0) {
            throw new Error("Offset must be greater than 0");
        }
    }
    private function _checkRange($rawParams)
    {
        if (empty($rawParams["range"]) && empty($rawParams["limit"])) {
            throw new Error("You must provide \"range\" or \"limit\"");
        }
        if (!empty($rawParams["range"]) && empty($rawParams["range"][\Component\Grid\Definition\TimeRange::INTERVAL]) && empty($rawParams["range"][\Component\Grid\Definition\TimeRange::FROM]) && empty($rawParams["range"][\Component\Grid\Definition\TimeRange::TO])) {
            throw new Error("Range must contain either \"interval\", \"from\" or \"to\"");
        }
    }
    private function _checkColumns()
    {
        if (!empty($this->_metrics) || !empty($this->_grouping) && is_array($this->_grouping)) {
            $this->_columns = array_merge($this->_columns, $this->_grouping, $this->_metrics);
        }
        if (empty($this->_columns)) {
            $this->_columns = array_keys($this->_definition->getColumns());
        }
        $this->_columns = array_unique($this->_columns);
    }
    private function _checkRequiredColumns()
    {
        if (!empty($this->_requiredColumns)) {
            $this->_requiredColumns = array_merge($this->_requiredColumns, $this->_columns);
        } else {
            $this->_requiredColumns = $this->_columns;
        }
        if (!empty($this->_filters)) {
            foreach ($this->_filters as $filter) {
                if (!empty($filter["name"])) {
                    $this->_requiredColumns[] = $filter["name"];
                }
            }
        }
        foreach ($this->_requiredColumns as $columnName) {
            $column = $this->_definition->getColumn($columnName);
            if ($column->getRequiredColumns()) {
                $this->_requiredColumns = array_merge($this->_requiredColumns, $column->getRequiredColumns());
            }
        }
        $this->_requiredColumns = array_unique($this->_requiredColumns);
    }
    private function _checkFilterByIp()
    {
        if (empty($this->_filters)) {
            return NULL;
        }
        foreach ($this->_filters as $n => $filter) {
            if ($filter["name"] == "ip" && $filter["operator"] != "IP_BEGINS_WITH") {
                if (is_array($filter["expression"])) {
                    foreach ($filter["expression"] as $i => $v) {
                        if (!is_numeric($v)) {
                            $this->_filters[$n]["expression"][$i] = ip2long($v);
                        }
                    }
                } else {
                    if (!is_numeric($filter["expression"]) && $filter["operator"] != \Component\Grid\Query\FilterItem::HAS_LABEL && $filter["operator"] != \Component\Grid\Query\FilterItem::HAS_NOT_LABEL) {
                        $this->_filters[$n]["expression"] = ip2long($filter["expression"]);
                    }
                }
            }
        }
    }
}

?>