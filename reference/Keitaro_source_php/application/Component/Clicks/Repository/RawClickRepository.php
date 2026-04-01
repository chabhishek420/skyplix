<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Repository;

class RawClickRepository extends \Traffic\Service\AbstractService
{
    private $_excludeRefTables = ["campaign", "stream"];
    public function findBySubId($subId)
    {
        $definition = new \Component\Clicks\Grid\ClicksDefinition();
        $fullTableName = $definition->getFullTableName();
        $table = $definition->getTable();
        $sql = [];
        $sql[] = "SELECT " . implode(", ", $this->_getSelect());
        $sql[] = "FROM " . $fullTableName . " as t_" . $table;
        $sql[] = implode(" ", $this->getJoins());
        $sql[] = "WHERE sub_id = " . \Core\Db\Db::quote($subId);
        $data = \Core\Db\Db::instance()->getRow(implode(" ", $sql));
        if (empty($data)) {
            return NULL;
        }
        $rawClick = new \Traffic\RawClick();
        $rawClick->setData($data);
        if ($rawClick->isLead()) {
            $rawClick->set("lead_revenue", $data["revenue"]);
        }
        if ($rawClick->isSale()) {
            $rawClick->set("sale_revenue", $data["revenue"]);
        }
        if ($rawClick->isRejected()) {
            $rawClick->set("rejected_revenue", $data["revenue"]);
        }
        return $rawClick;
    }
    private function _getSelect()
    {
        $definition = new \Component\Clicks\Grid\ClicksDefinition();
        $items = [];
        foreach ($definition->getColumns() as $column) {
            if (!in_array($column->getName(), $this->_excludeRefTables)) {
                $items[] = $column->getInnerSelectSql();
            }
        }
        return $items;
    }
    private function getJoins()
    {
        $definition = new \Component\Clicks\Grid\ClicksDefinition();
        $joins = [];
        foreach ($definition->getRelations() as $relation) {
            if (!in_array($relation->getName(), $this->_excludeRefTables)) {
                $joins = array_merge($joins, $relation->getJoins());
            }
        }
        return $joins;
    }
}

?>