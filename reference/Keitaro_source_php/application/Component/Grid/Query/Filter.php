<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class Filter
{
    private $_definition = NULL;
    private $_rawFilters = [];
    public function __construct($rawFilters, \Component\Grid\Definition\GridDefinition $definition)
    {
        if (empty($rawFilters)) {
            $rawFilters = [];
        }
        $this->_rawFilters = $rawFilters;
        $this->_definition = $definition;
    }
    public function add($filter)
    {
        if (!empty($filter)) {
            $this->_rawFilters[] = $filter;
        }
    }
    public function hasDatetimeFilter()
    {
        foreach ($this->_rawFilters as $rawFilter) {
            if ($rawFilter["name"] == "datetime") {
                return true;
            }
        }
        return false;
    }
    public function getWhereSql()
    {
        $items = [];
        foreach ($this->_rawFilters as $rawFilter) {
            $field = $this->_definition->getColumn($rawFilter["name"]);
            if (!$field->isMetric()) {
                $filterItem = $this->_build($field, $rawFilter);
                $items[] = $filterItem;
            }
        }
        $items = array_filter($items);
        if (count($items)) {
            return "WHERE " . implode(" AND ", $items);
        }
    }
    public function getHavingSql()
    {
        $items = [];
        foreach ($this->_rawFilters as $rawFilter) {
            $column = $this->_definition->getColumn($rawFilter["name"]);
            if ($column->isMetric()) {
                $filterItem = new FilterItem($rawFilter);
                $columnName = $column->getName();
                $filterItem->setName("`" . $columnName . "`");
                $items[] = $filterItem->toSql();
            }
        }
        $items = array_filter($items);
        if (count($items)) {
            return "HAVING " . implode(" AND ", $items);
        }
    }
    private function _build(\Component\Grid\Definition\Column $column, $rawFilter)
    {
        $filterItem = new FilterItem($rawFilter);
        $columnName = $column->getName();
        if ($column->isRelation()) {
            $select = $column->getInnerSelect();
            if (empty($select)) {
                throw new Error("You must provide 'select' for column '" . $columnName . "'");
            }
            $filterItem->setName(key($select));
        } else {
            $table = $this->_definition->getTable();
            $filterItem->setName("t_" . $table . "." . $columnName);
        }
        $this->_checkHasLabelFilter($column, $filterItem, $rawFilter);
        return $filterItem->toSql();
    }
    private function _checkHasLabelFilter(\Component\Grid\Definition\Column $column, FilterItem $filterItem, $rawFilter)
    {
        if ($filterItem->getOperator() != FilterItem::HAS_LABEL && $filterItem->getOperator() != FilterItem::HAS_NOT_LABEL) {
            return NULL;
        }
        $campaignFilter = $this->_findCampaignFilter();
        if (!$campaignFilter || empty($campaignFilter["expression"])) {
            $campaignId = NULL;
        } else {
            $campaignId = $campaignFilter["expression"];
        }
        $filterExpressions = $filterItem->getExpression();
        if (!empty($filterExpressions)) {
            $ids = \Component\Reports\Repository\LabelRepository::instance()->getRefIds($campaignId, $rawFilter["name"], $filterExpressions);
            if ($filterItem->getOperator() == FilterItem::HAS_LABEL) {
                $operator = FilterItem::IN_LIST;
            } else {
                $operator = FilterItem::NOT_IN_LIST;
            }
            $filterItem->setName($column->getGroupableColumn())->setOperator($operator)->setExpression($ids);
        } else {
            $filterItem->setName($column->getGroupableColumn())->setOperator(FilterItem::NOT_IN_LIST)->setExpression("-1");
        }
    }
    private function _findCampaignFilter()
    {
        foreach ($this->_rawFilters as $key => $rawFilter) {
            if ($rawFilter["name"] == "campaign_id" && $rawFilter["operator"] == FilterItem::EQUALS) {
                return $rawFilter;
            }
        }
    }
}

?>