<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Repository;

class LabelRepository extends \Core\Entity\Repository\EntityRepository
{
    const WHITELIST = "whitelist";
    const BLACKLIST = "blacklist";
    public function definition()
    {
        return \Component\Reports\Model\Label::definition();
    }
    public function getLabelVariations()
    {
        return [["value" => WHITELIST, "label" => \Core\Locale\LocaleService::t("reports.labels." . WHITELIST), "icon" => "ion-thumbsup grid-filter-label-whitelist"], ["value" => BLACKLIST, "label" => \Core\Locale\LocaleService::t("reports.labels." . BLACKLIST), "icon" => "ion-thumbsdown grid-filter-label-blacklist"]];
    }
    public function retRefNameVariations()
    {
        $items = [];
        $definition = new \Component\Reports\Grid\ReportDefinition();
        foreach ($definition->getColumns() as $column) {
            if ($column->isLabelsAllowed()) {
                $items[] = ["value" => $column->getName(), "name" => \Core\Locale\LocaleService::t($column->getTitle())];
            }
        }
        return $items;
    }
    public function labelsFor(\Traffic\Model\Campaign $campaign, $refName, $labelName = NULL)
    {
        $refDefinition = \Component\Reports\Service\LabelService::instance()->getRefDefinition($refName);
        $labelsTableName = $this->definition()->tableName();
        $refTableName = $refDefinition->tableName();
        $where = [];
        $where[] = "labels.ref_name = " . \Core\Db\Db::quote($refName);
        $where[] = "campaign_id = " . \Core\Db\Db::quote($campaign->getId());
        if (!empty($labelName)) {
            $where[] = "label_name = " . \Core\Db\Db::quote($labelName);
        }
        $sql = "SELECT ref.value as value, labels.label_name as label_name\n            FROM " . $labelsTableName . " labels\n            LEFT JOIN " . $refTableName . " ref ON ref.id = labels.ref_id\n            WHERE " . implode(" AND ", $where) . "\n            ORDER BY labels.id";
        $result = [];
        foreach (\Core\Db\Db::instance()->execute($sql) as $row) {
            if ($refName == \Component\Reports\Service\LabelService::IP) {
                $row["value"] = long2ip($row["value"]);
            }
            $result[$row["value"]] = $row["label_name"];
        }
        return $result;
    }
    public function getRefIds($campaignId, $refName, $labelName)
    {
        $where = [];
        if ($campaignId) {
            $where[] = "campaign_id = " . \Core\Db\Db::quote($campaignId);
        }
        $where[] = "ref_name = " . \Core\Db\Db::quote($refName);
        $where[] = "label_name = " . \Core\Db\Db::quote($labelName);
        $result = $this->pluck(implode(" AND ", $where), "ref_id");
        return array_unique($result);
    }
}

?>