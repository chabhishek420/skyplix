<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class Query
{
    private $_select = NULL;
    private $_joins = NULL;
    private $_from = NULL;
    private $_filters = NULL;
    private $_sort = NULL;
    private $_limit = NULL;
    private $_offset = NULL;
    private $_grouping = NULL;
    private $_options = ["select", "from", "filters", "sort", "grouping", "limit", "offset", "joins"];
    public function __construct($options)
    {
        foreach ($options as $name => $option) {
            if ($name == "dimensions") {
                $name = "grouping";
            }
            if (!in_array($name, $this->_options)) {
                throw new Error("Option '" . $name . "' is incorrect for Query object");
            }
            $name = "_" . $name;
            $this->{$name} = $option;
        }
    }
    public function setSelect(Select $select)
    {
        $this->_select = $select;
        return $this;
    }
    public function getSelect()
    {
        return $this->_select;
    }
    public function setFilters(Filter $filters)
    {
        $this->_filters = $filters;
        return $this;
    }
    public function getFilters()
    {
        return $this->_filters;
    }
    public function getJoins()
    {
        return $this->_joins;
    }
    public function getOffset()
    {
        return $this->_offset;
    }
    public function getLimit()
    {
        return $this->_limit;
    }
    public function getGrouping()
    {
        return $this->_grouping;
    }
    public function setOffset(Offset $offset = NULL)
    {
        $this->_offset = $offset;
        return $this;
    }
    public function setGrouping(Grouping $grouping = NULL)
    {
        $this->_grouping = $grouping;
        return $this;
    }
    public function setSort(Sort $sort = NULL)
    {
        $this->_sort = $sort;
        return $this;
    }
    public function setLimit(Limit $limit = NULL)
    {
        $this->_limit = $limit;
        return $this;
    }
    public function setJoins(Joins $joins = NULL)
    {
        $this->_joins = $joins;
        return $joins;
    }
    public function toSql()
    {
        $sql[] = $this->_select->getInnerSql();
        $hasDatetimeFilter = $this->_filters->hasDatetimeFilter();
        $sql[] = $this->_from->getSql($hasDatetimeFilter);
        if (!empty($this->_joins)) {
            $sql[] = $this->_joins->getSql();
        }
        if (!empty($this->_filters)) {
            $sql[] = $this->_filters->getWhereSql();
        }
        if (!empty($this->_grouping)) {
            $sql[] = $this->_grouping->getSql();
        }
        $outerSelect = $this->_select->getOuterSql();
        if (!empty($outerSelect)) {
            $sql = [$this->_subQuery($sql, $outerSelect)];
        }
        if (!empty($this->_filters)) {
            $sql[] = $this->_filters->getHavingSql();
        }
        if (!empty($this->_sort)) {
            $sql[] = $this->_sort->getSql();
        }
        if (!empty($this->_limit)) {
            $sql[] = $this->_limit->getSql();
        }
        if (!empty($this->_offset)) {
            $sql[] = $this->_offset->getSql();
        }
        $sql = array_filter($sql);
        return $this->_addSqlInstructions(implode(" ", $sql));
    }
    public function fetchRows()
    {
        return \Core\Db\Db::slaveInstance()->fetchRows($this->toSql());
    }
    public function fetchOne()
    {
        return \Core\Db\Db::slaveInstance()->getOne($this->toSql());
    }
    private function _subQuery($sql, $outerSelect)
    {
        $innerSql = implode(" ", $sql);
        return $outerSelect . " FROM ( " . $innerSql . " ) as x";
    }
    private function _addSqlInstructions($sql)
    {
        return preg_replace("/SELECT /", "SELECT SQL_CALC_FOUND_ROWS ", $sql, 1);
    }
}

?>