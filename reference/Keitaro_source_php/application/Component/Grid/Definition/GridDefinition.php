<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Definition;

abstract class GridDefinition
{
    protected $_table = NULL;
    protected $_rangeTimeField = NULL;
    protected $_columns = [];
    protected $_url = NULL;
    protected $_rangeIntervals = [];
    protected $_details = NULL;
    protected $_dictionaries = NULL;
    protected $_relations = [];
    public function __construct()
    {
        $this->initColumns();
        $this->initRelations();
    }
    public abstract function initColumns();
    public abstract function initRelations();
    public abstract function getTitle();
    public function addColumn(Column $field)
    {
        $field->setDefinition($this);
        $this->_columns[$field->getName()] = $field;
    }
    public function removeColumn($fieldName)
    {
        unset($this->_columns[$fieldName]);
    }
    public function whitelist($list)
    {
        foreach ($this->_columns as $column) {
            if (!in_array($column->getName(), $list)) {
                $this->removeColumn($column->getName());
            }
        }
    }
    public function getColumns()
    {
        return $this->_columns;
    }
    public function addRelation(Relation $relation)
    {
        $this->_relations[$relation->getName()] = $relation;
        $relation->setDefinition($this);
    }
    public function getRelations()
    {
        return $this->_relations;
    }
    public function getRelation($name)
    {
        if (!isset($this->_relations[$name])) {
            throw new \Exception("Relation with name '" . $name . "' not exists");
        }
        return $this->_relations[$name];
    }
    public function getColumn($name)
    {
        if (empty($this->_columns[$name])) {
            $names = implode(", ", array_keys($this->_columns));
            throw new Error("Column '" . $name . "' is not defined (columns of the grid " . $names . ")");
        }
        return $this->_columns[$name];
    }
    public function getSummaryColumns()
    {
        $columns = [];
        foreach ($this->_columns as $field) {
            if ($field->isSummarizable()) {
                $columns[] = $field->getName();
            }
        }
        return $columns;
    }
    public function getPrimaryColumnKeys()
    {
        $columns = [];
        foreach ($this->_columns as $column) {
            if ($column->isPrimary()) {
                $columns[] = $column->getName();
            }
        }
        return $columns;
    }
    public function getTable()
    {
        return $this->_table;
    }
    public function getFullTableName()
    {
        return \Core\Db\Db::getPrefix() . $this->_table;
    }
    public function getRangeTimeField()
    {
        return $this->_rangeTimeField;
    }
    public function listAsOptions($needHidden = false)
    {
        $options = [];
        foreach ($this->_columns as $column) {
            if ($needHidden || !$column->isHidden()) {
                $options[] = ["category" => \Core\Locale\LocaleService::t("grid.categories." . $column->getCategory()), "name" => \Core\Locale\LocaleService::instance()->exists($column->getTitle()) ? \Core\Locale\LocaleService::t($column->getTitle()) : \Traffic\Tools\Tools::humanize($column->getName()), "value" => $column->getName()];
            }
        }
        return $options;
    }
    public function getGridDefinition()
    {
        $columns = [];
        foreach ($this->_columns as $column) {
            $columns[] = $column->toGridDefinition();
        }
        return ["url" => $this->_url, "details" => $this->_details, "range_intervals" => $this->_rangeIntervals, "columns" => $columns];
    }
    public function getDictionaries()
    {
        if (empty($this->_dictionaries)) {
            $this->_dictionaries = array_filter($this->_relations, function ($relation) {
                return $relation->isDict();
            });
        }
        return $this->_dictionaries;
    }
}

?>