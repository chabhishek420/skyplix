<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class SortItem
{
    private $_column = NULL;
    private $_order = NULL;
    private $_validOrders = NULL;
    const ASC = "ASC";
    const DESC = "DESC";
    public function __construct(\Component\Grid\Definition\Column $column, $info)
    {
        $this->_column = $column;
        $this->_order = isset($info["order"]) ? strtoupper($info["order"]) : NULL;
        if (!empty($this->_order) && !in_array($this->_order, $this->_validOrders)) {
            throw new Error("Incorrect order " . $this->_order);
        }
    }
    public function toSql()
    {
        return $this->_column->toOrderSql($this->_getOrder());
    }
    private function _getOrder()
    {
        if (!empty($this->_order)) {
            return " " . $this->_order;
        }
        return NULL;
    }
}

?>