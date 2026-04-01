<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Definition;

class Column
{
    private $_validOptions = NULL;
    private $_validTypes = NULL;
    private $_gridDefinitionOptions = NULL;
    private $_primary = NULL;
    private $_name = NULL;
    private $_title = NULL;
    private $_thTitle = NULL;
    private $_type = NULL;
    private $_sortable = NULL;
    private $_sortBy = NULL;
    private $_filter = NULL;
    private $_groupable = NULL;
    private $_category = NULL;
    private $_groupBy = NULL;
    private $_innerSelect = NULL;
    private $_outerSelect = NULL;
    private $_relation = NULL;
    private $_summary = NULL;
    private $_metric = NULL;
    private $_visible = NULL;
    private $_requiredColumns = NULL;
    private $_formatter = NULL;
    private $_decorator = NULL;
    private $_excludeFromDetails = NULL;
    private $_dictionary = NULL;
    private $_detailsColumn = NULL;
    private $_fractionSize = NULL;
    private $_virtual = NULL;
    private $_template = NULL;
    private $_labelsAllowed = NULL;
    private $_width = NULL;
    private $_resizable = NULL;
    private $_hidden = NULL;
    private $_definition = NULL;
    const BOOLEAN = "boolean";
    const INTEGER = "integer";
    const DECIMAL = "decimal";
    const STRING = "string";
    const DATETIME = "datetime";
    const DATE = "date";
    const IP = "ip";
    const VERSION = "version";
    const ENUM = "enum";
    const JSON = "json";
    const PRIMARY = "primary";
    const NAME = "name";
    const TITLE = "title";
    const TH_TITLE = "th_title";
    const TYPE = "type";
    const INNER_SELECT = "inner_select";
    const OUTER_SELECT = "outer_select";
    const SORTABLE = "sortable";
    const FILTER = "filter";
    const GROUPABLE = "groupable";
    const CATEGORY = "category";
    const SORT_BY = "sort_by";
    const GROUP_BY = "group_by";
    const FORMATTER = "formatter";
    const AXIS = "axis";
    const RELATION = "relation";
    const SUMMARY = "summary";
    const SUMMARY_AVERAGE = "summary_average";
    const METRIC = "metric";
    const REQUIRED_COLUMNS = "required_columns";
    const DECORATOR = "decorator";
    const EXCLUDE_FROM_DETAILS = "exclude_from_details";
    const DICTIONARY = "dictionary";
    const HIDDEN = "hidden";
    const CLICKABLE = "clickable";
    const FRACTION_SIZE = "fraction_size";
    const VIRTUAL = "virtual";
    const TEMPLATE = "template";
    const LABELS_ALLOWED = "labels_allowed";
    const WIDTH = "width";
    const RESIZABLE = "resizable";
    const FORMATTER_MONEY = "money";
    const FORMATTER_MONEY_H = "money_h";
    const FORMATTER_MONEY_H_NEGATIVE = "money_negative";
    const FORMATTER_PROFITABILITY = "profitability";
    const FORMATTER_DATETIME = "datetime";
    const FORMATTER_TIME = "time";
    const FORMATTER_BOOLEAN = "boolean";
    const FORMATTER_INTEGER = "integer";
    const FORMATTER_DECIMAL = "decimal";
    const FORMATTER_PERCENTAGE = "percentage";
    const FORMATTER_PERCENTAGE_H = "percentage_h";
    const FORMATTER_PERCENTAGE_H_NEGATIVE = "percentage_h_negative";
    const FORMATTER_HOUR = "hour";
    const FORMATTER_DAY_HOUR = "day_hour";
    const FORMATTER_MONTH = "month";
    const FORMATTER_WEEK = "week";
    const FORMATTER_WEEKDAY = "weekday";
    const FORMATTER_TIME_DIFF = "time_diff";
    const FORMATTER_LIST = "list";
    const FORMATTER_OBJECT = "object";
    public function __construct($name, $options)
    {
        $this->_name = $name;
        $this->set($options);
        if (is_null($this->_title)) {
            $this->_title = "grid." . $this->_name;
        }
        if (is_null($this->_thTitle)) {
            $this->_thTitle = $this->_title;
        }
        if (is_null($this->_width)) {
            $this->_width = 100;
        }
        if (is_null($this->_resizable)) {
            $this->_resizable = true;
        }
    }
    public function setDefinition(GridDefinition $definition)
    {
        $this->_definition = $definition;
        $this->_updateSelect();
    }
    public function set($options)
    {
        foreach ($options as $option => $value) {
            if (!in_array($option, $this->_validOptions)) {
                throw new Error("Option '" . $option . "' is unknown");
            }
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($option, true);
            $this->{$name} = $value;
            $this->_validateType();
        }
    }
    private function _updateSelect()
    {
        if (empty($this->_innerSelect) && empty($this->_outerSelect) && !$this->_virtual) {
            $table = $this->getDefinition()->getTable();
            $this->_innerSelect = ["t_" . $table . "." . $this->getName() => $this->getName()];
        }
    }
    public function getInnerSelect()
    {
        return $this->_innerSelect;
    }
    public function getOuterSelect()
    {
        return $this->_outerSelect;
    }
    public function getType()
    {
        return $this->_type;
    }
    public function getTitle()
    {
        return $this->_title;
    }
    public function getThTitle()
    {
        return $this->_thTitle;
    }
    public function getName()
    {
        return $this->_name;
    }
    public function getCategory()
    {
        return $this->_category;
    }
    public function getRelation()
    {
        return $this->_relation;
    }
    public function isHidden()
    {
        return $this->_hidden;
    }
    public function isLabelsAllowed()
    {
        return $this->_labelsAllowed;
    }
    public function isRelation()
    {
        return !empty($this->_relation);
    }
    public function isSummarizable()
    {
        return !empty($this->_summary);
    }
    public function getRequiredColumns()
    {
        return $this->_requiredColumns;
    }
    public function getDecorator()
    {
        return $this->_decorator;
    }
    public function getFormatter()
    {
        return $this->_formatter;
    }
    public function getDefinition()
    {
        return $this->_definition;
    }
    public function isMetric()
    {
        return $this->_metric;
    }
    public function isBoolean()
    {
        return $this->_type == BOOLEAN;
    }
    public function isGroupable()
    {
        return $this->_groupable;
    }
    public function isSortable()
    {
        return $this->_sortable;
    }
    public function isVirtual()
    {
        return $this->_virtual;
    }
    public function getInnerSelectSql($summary = false)
    {
        return $this->_toSelect($this->_innerSelect, $summary);
    }
    public function getOuterSelectSql($summary = false)
    {
        return $this->_toSelect($this->_outerSelect, $summary);
    }
    private function _toSelect($selectHash, $summary)
    {
        if (empty($selectHash)) {
            return NULL;
        }
        $field = key($selectHash);
        if ($summary && !stristr($field, "sum") && !stristr($field, "count")) {
            $field = "SUM(" . $field . ")";
        }
        return $field . " AS " . current($selectHash);
    }
    public function toJoins()
    {
        if ($this->isRelation()) {
            $relation = $this->_definition->getRelation($this->getRelation());
            return $relation->getJoins();
        }
        return [];
    }
    public function getGroupableColumn()
    {
        $relation = $this->_definition->getRelation($this->getRelation());
        return $relation->getGroupableColumn();
    }
    public function toOrderSql($order)
    {
        if (!empty($this->_sortBy)) {
            return "`" . $this->_sortBy . "`" . $order;
        }
        $name = $this->getName();
        return "`" . $name . "`" . $order;
    }
    public function toGroupSql()
    {
        if (!empty($this->_groupBy)) {
            return $this->_groupBy;
        }
        if ($this->isRelation()) {
            return $this->getGroupableColumn();
        }
        $table = $this->_definition->getTable();
        $name = $this->getName();
        return "t_" . $table . "." . $name;
    }
    public function toGridDefinition()
    {
        $definition = [];
        foreach ($this->_gridDefinitionOptions as $option) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($option, true);
            if (isset($this->{$name})) {
                $definition[$option] = $this->{$name};
            }
        }
        return $definition;
    }
    private function _validateType()
    {
        if (!in_array($this->_type, $this->_validTypes)) {
            $types = implode(", ", $this->_validTypes);
            throw new Error("Field " . $this->_name . " has incorrect type '" . $this->_type . "'. " . "Allowed " . $types);
        }
        if (!empty($this->_innerSelect) && !is_array($this->_innerSelect)) {
            throw new Error("Select for field '" . $this->_name . "' must be array ['expression' => 'as_field']");
        }
    }
}

?>