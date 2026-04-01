<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cleaner\Service;

class CleanerService extends \Traffic\Service\AbstractService
{
    private function _cleanData($clickWhere, $conversionWhere)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("Deleting clicks");
        \Core\Db\DataService::instance()->deleteAll(\Traffic\Model\Click::definition(), $clickWhere, true);
        \Traffic\Logging\Service\LoggerService::instance()->debug("Deleting conversions");
        \Core\Db\DataService::instance()->deleteAll(\Traffic\Model\Conversion::definition(), $conversionWhere, true);
    }
    public function deleteStats($options)
    {
        if (!is_array($options)) {
            throw new \Core\Exception("\$options for cleanRange must be an array");
        }
        if (empty($options["timezone"])) {
            $tz = new \DateTimeZone("UTC");
        } else {
            $tz = new \DateTimeZone($options["timezone"]);
        }
        if (!empty($options["start_date"])) {
            $startDate = new \DateTime($options["start_date"], $tz);
            $startDate->setTimezone(new \DateTimeZone("UTC"));
        } else {
            $startDate = new \DateTime("01-01-1999");
        }
        if (!empty($options["end_date"])) {
            $endDate = new \DateTime($options["end_date"], $tz);
            $endDate->setTimezone(new \DateTimeZone("UTC"));
        } else {
            $endDate = new \DateTime("2099-01-01");
        }
        $format = \Core\Model\AbstractModel::DATETIME_FORMAT;
        $clickWhere = "(`datetime` BETWEEN " . \Core\Db\Db::quote($startDate->format($format)) . " AND " . \Core\Db\Db::quote($endDate->format($format)) . ")";
        $conversionWhere = "(`click_datetime` BETWEEN " . \Core\Db\Db::quote($startDate->format($format)) . " AND " . \Core\Db\Db::quote($endDate->format($format)) . ")";
        if (!empty($options["campaign_id"])) {
            $clickWhere .= " AND campaign_id = " . (int) $options["campaign_id"];
            $conversionWhere .= " AND campaign_id = " . (int) $options["campaign_id"];
            \Traffic\Logging\Service\LoggerService::instance()->debug("Deleting data for campaign " . (int) $options["campaign_id"]);
        }
        \Traffic\Logging\Service\LoggerService::instance()->debug("Deleting data from " . $startDate->format($format) . " to " . $endDate->format($format));
        $this->_cleanData($clickWhere, $conversionWhere);
    }
    public function pruneClicks($ttlInDays)
    {
        $format = \Core\Model\AbstractModel::DATETIME_FORMAT;
        $maxDate = new \DateTime();
        $maxDate->modify("-" . $ttlInDays . "days");
        $clickWhere = "(`datetime` < " . \Core\Db\Db::quote($maxDate->format($format)) . ")";
        $conversionWhere = "(`click_datetime` < " . \Core\Db\Db::quote($maxDate->format($format)) . ")";
        \Traffic\Logging\Service\LoggerService::instance()->debug("Deleting data less than " . $maxDate->format($format));
        $this->_cleanData($clickWhere, $conversionWhere);
    }
    public function pruneVisitors()
    {
        $clicksTable = \Traffic\Model\Click::getTableName();
        $visitorsTable = \Component\Clicks\Model\Visitor::getTableName();
        $sql = "DELETE FROM " . $visitorsTable . " WHERE NOT EXISTS \n          (SELECT * FROM " . $clicksTable . " WHERE visitor_id = " . $visitorsTable . ".id)";
        \Core\Db\Db::instance()->executeUntilFinished($sql);
    }
    public function pruneConversions()
    {
        $clicksTable = \Traffic\Model\Click::getTableName();
        $conversionsTable = \Traffic\Model\Conversion::getTableName();
        $sql = "DELETE FROM " . $conversionsTable . " WHERE NOT EXISTS \n          (SELECT * FROM " . $clicksTable . " WHERE click_id = " . $conversionsTable . ".click_id)";
        \Core\Db\Db::instance()->executeUntilFinished($sql);
    }
    public function pruneClickLinks()
    {
        $sql = "DELETE link\n                FROM " . \Component\Clicks\Model\ClickLink::getTableName() . " link\n                LEFT OUTER JOIN " . \Traffic\Model\Click::getTableName() . " clicks ON link.sub_id = clicks.sub_id \n                WHERE clicks.click_id IS NULL";
        \Core\Db\Db::instance()->execute($sql);
    }
    public function pruneReferences()
    {
        $clicksTable = \Traffic\Model\Click::getTableName();
        $visitorsTable = \Component\Clicks\Model\Visitor::getTableName();
        $definition = new \Component\Clicks\Grid\ClicksDefinition();
        foreach ($definition->getRelations() as $relation) {
            if ($relation->isDict() && !$relation->isSubId()) {
                $relationTable = $relation->getTableName();
                $foreignKey = $relation->getForeignKey();
                $mainTable = $relation->getThrough() == "visitors" ? $visitorsTable : $clicksTable;
                $this->_clearRelationTable($relationTable, $foreignKey, $mainTable);
            }
        }
        $joins = [];
        $conditions = [];
        for ($i = 1; $i <= \Traffic\Model\Click::getSubIdCount(); $i++) {
            $alias = "clicks" . $i;
            $joins[] = " LEFT OUTER JOIN " . $clicksTable . " " . $alias . " ON ref.id = " . $alias . ".sub_id_" . $i . "_id ";
            $conditions[] = " " . $alias . ".click_id IS NULL ";
        }
        $subIdTable = \Component\Clicks\Model\Ref\SubIdN::getTableName();
        $sql = "DELETE ref\n                FROM " . $subIdTable . " ref " . implode(" ", $joins) . " WHERE " . implode(" AND ", $conditions);
        \Core\Db\Db::instance()->execute($sql);
    }
    private function _clearRelationTable($relationTable, $foreignKey, $mainTable)
    {
        $db = \Core\Db\Db::instance();
        $batchSize = 100000;
        $firstId = 0;
        $relationIds = $db->getAll("SELECT id FROM " . $relationTable . " WHERE id > " . $firstId . " LIMIT " . $batchSize);
        if (count($relationIds) !== 0) {
            $relationIds = array_column($relationIds, "id");
            $relationIdsString = implode(",", $relationIds);
            $firstId = end($relationIds);
            $relationIdsInClicks = $db->getAll("SELECT DISTINCT " . $foreignKey . " FROM " . $mainTable . " WHERE " . $foreignKey . " IN (" . $relationIdsString . ")");
            $relationIdsInClicks = array_column($relationIdsInClicks, $foreignKey);
            $needlessIds = array_diff($relationIds, $relationIdsInClicks);
            if (count($needlessIds) !== 0) {
                $needlessIds = implode(",", $needlessIds);
                $db->execute("DELETE FROM " . $relationTable . " WHERE id IN (" . $needlessIds . ")");
            }
            if (count($relationIds) === $batchSize) {
            }
        }
    }
}

?>