<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Builder;

class GridPostProcessor
{
    private $_query = NULL;
    private $_definition = NULL;
    private $_memoized = [];
    public function __construct(\Component\Grid\Query\Query $query, \Component\Grid\Definition\GridDefinition $definition)
    {
        $this->_query = $query;
        $this->_definition = $definition;
    }
    public function process($rows)
    {
        $rows = $this->_fixLpCtr($rows);
        return $rows;
    }
    private function _fixLpCtr($rows)
    {
        if ($this->_hasLpCtrColumn() && $this->_hasLpColumn() && $this->_hasOfferColumn()) {
            foreach ($rows as $i => $row) {
                $lpId = $row["landing_id"];
                $clicks = $this->_getSummaryClicksForLp($lpId);
                if ($clicks == 0) {
                    $row["lp_ctr"] = 0;
                } else {
                    $row["lp_ctr"] = $row["lp_clicks"] / $clicks * 0;
                    $rows[$i] = $row;
                }
            }
        }
        return $rows;
    }
    private function _hasOfferColumn()
    {
        $columnNames = $this->_query->getSelect()->getColumnNames();
        return in_array("offer_id", $columnNames) || in_array("offer", $columnNames);
    }
    private function _hasLpColumn()
    {
        $columnNames = $this->_query->getSelect()->getColumnNames();
        return in_array("offer_id", $columnNames) || in_array("offer", $columnNames);
    }
    private function _hasLpCtrColumn()
    {
        $columnNames = $this->_query->getSelect()->getColumnNames();
        return in_array("lp_ctr", $columnNames);
    }
    private function _getSummaryClicksForLp($lpId)
    {
        if (array_key_exists($lpId, $this->_memoized)) {
            return $this->_memoized[$lpId];
        }
        $copier = new \DeepCopy\DeepCopy();
        $summaryQuery = $copier->copy($this->_query);
        $select = new \Component\Grid\Query\Select(["clicks"], $this->_definition, true);
        $filters = $summaryQuery->getFilters();
        $filters->add(["name" => "landing_id", "operator" => \Component\Grid\Query\FilterItem::EQUALS, "expression" => $lpId]);
        $summaryQuery->setSelect($select)->setFilters($filters)->setSort(NULL)->setLimit(NULL)->setOffset(NULL)->setGrouping(NULL);
        $clicks = $summaryQuery->fetchOne();
        $this->_memoized[$lpId] = $clicks;
        return $clicks;
    }
}

?>