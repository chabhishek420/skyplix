<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Service;

class LabelService extends \Core\Entity\Service\EntityService
{
    const IP = "ip";
    public function definition()
    {
        return \Component\Reports\Model\Label::definition();
    }
    public function set(\Traffic\Model\Campaign $campaign, $refName, $refId, $labelName = NULL)
    {
        if (empty($labelName)) {
            $this->remove($campaign, $refName, $refId);
        } else {
            $tableName = \Component\Reports\Model\Label::getTableName();
            $sql = "INSERT INTO " . $tableName . " (campaign_id, label_name, ref_name, ref_id) VALUES\n            (\n            " . \Core\Db\Db::quote($campaign->getId()) . ",\n            " . \Core\Db\Db::quote($labelName) . ",\n            " . \Core\Db\Db::quote($refName) . ",\n            " . \Core\Db\Db::quote($refId) . "\n            )\n            ON DUPLICATE KEY UPDATE label_name=" . \Core\Db\Db::quote($labelName);
            \Core\Db\Db::instance()->execute($sql);
        }
    }
    public function remove(\Traffic\Model\Campaign $campaign, $refName, $refId)
    {
        $where = [];
        $where[] = "campaign_id = " . \Core\Db\Db::quote($campaign->getId());
        $where[] = "ref_name = " . \Core\Db\Db::quote($refName);
        $where[] = "ref_id = " . \Core\Db\Db::quote($refId);
        return $this->directDeleteAll(implode(" AND ", $where));
    }
    public function updateLabels(\Traffic\Model\Campaign $campaign, $refName, $items)
    {
        $refDefinition = $this->getRefDefinition($refName);
        foreach ($items as $refValue => $label) {
            if ($refName == IP) {
                $refValue = ip2long($refValue);
            } else {
                $refValue = (int) $refValue;
            }
            $row = \Core\Db\DataRepository::instance()->fetchRow($refDefinition, "id", "value = " . \Core\Db\Db::quote($refValue));
            if (!empty($row["id"])) {
                $this->set($campaign, $refName, $row["id"], $label);
            }
        }
    }
    public function replaceList(\Traffic\Model\Campaign $campaign, $labelName, $refName, $refValues)
    {
        if (empty($labelName)) {
            throw new \Core\Application\Exception\Error("Empty label_name");
        }
        if (empty($refName)) {
            throw new \Core\Application\Exception\Error("Empty ref_name");
        }
        if ($refName == "ip") {
            $refValues = array_map(function ($ip) {
                return ip2long($ip);
            }, $refValues);
        }
        $refDefinition = $this->getRefDefinition($refName);
        $where = "value IN (" . implode(",", \Core\Db\Db::quote($refValues)) . ")";
        $update = \Core\Db\DataRepository::instance()->pluck($refDefinition, $where, "id");
        $this->_deleteAllExcept($campaign, $labelName, $refName, $update);
        foreach ($update as $refId) {
            $this->set($campaign, $refName, $refId, $labelName);
        }
    }
    private function _deleteAllExcept(\Traffic\Model\Campaign $campaign, $labelName, $refName, $leaveRefIds)
    {
        if (!count($leaveRefIds)) {
            $leaveRefIds = [-1];
        }
        $where = [];
        $where[] = "campaign_id = " . \Core\Db\Db::quote($campaign->getId());
        $where[] = "label_name = " . \Core\Db\Db::quote($labelName);
        $where[] = "ref_name = " . \Core\Db\Db::quote($refName);
        $where[] = "id NOT IN (" . implode(",", \Core\Db\Db::quote($leaveRefIds)) . ")";
        LabelService::instance()->directDeleteAll(implode(" AND ", $where));
    }
    public function getRefDefinition($refName)
    {
        switch ($refName) {
            case "ip":
                return \Component\Clicks\Model\Ref\Ip::definition();
                break;
            case "source":
                return \Component\Clicks\Model\Ref\Source::definition();
                break;
            case "ad_campaign_id":
                return \Component\Clicks\Model\Ref\AdCampaignId::definition();
                break;
            case "creative_id":
                return \Component\Clicks\Model\Ref\CreativeId::definition();
                break;
            case "keyword":
                return \Component\Clicks\Model\Ref\Keyword::definition();
                break;
            case "ad_campaign_idn":
                return \Component\Clicks\Model\Ref\AdCampaignId::definition();
                break;
            default:
                if (strstr($refName, "sub_id_")) {
                    return \Component\Clicks\Model\Ref\SubIdN::definition();
                }
                throw new \Core\Application\Exception\Error("Incorrect ref_name " . $refName);
        }
    }
}

?>