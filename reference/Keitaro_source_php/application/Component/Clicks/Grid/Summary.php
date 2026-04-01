<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Grid;

class Summary
{
    private $_query = NULL;
    private $_definition = NULL;
    public function __construct(\Component\Grid\Query\Query $query, \Component\Grid\Definition\GridDefinition $definition)
    {
        $copier = new \DeepCopy\DeepCopy();
        $this->_query = $copier->copy($query);
        $this->_definition = $definition;
    }
    public function toSql()
    {
        $select = new \Component\Grid\Query\Select($this->_getColumns(), $this->_definition, true);
        return $this->_query->setSelect($select)->setSort(NULL)->setLimit(NULL)->setOffset(NULL)->setGrouping(NULL)->toSql();
    }
    public function get()
    {
        $columns = $this->_getColumns();
        if (empty($columns)) {
            return [];
        }
        $sql = $this->toSql();
        $row = \Core\Db\Db::instance()->fetchRow($sql);
        $decorator = new \Component\Grid\Builder\Decorator($this->_getColumns(), $this->_definition);
        return $decorator->decorateRow($row);
    }
    private function _getColumns()
    {
        return array_intersect($this->_query->getSelect()->getColumnNames(), $this->_definition->getSummaryColumns());
    }
}

?>