<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Repository;

class CampaignRepository extends \Core\Entity\Repository\EntityRepository
{
    private $_uniquenessMethods = NULL;
    private $_costTypes = NULL;
    private $_bindVisitorTypes = NULL;
    public function definition()
    {
        return \Traffic\Model\Campaign::definition();
    }
    public function getNameById($campaignId)
    {
        $campaign = $this->findFirst("id = " . (int) $campaignId);
        if ($campaign) {
            return $campaign->getName();
        }
        return $campaignId;
    }
    public function getDeletedCampaignIds()
    {
        return $this->pluck("state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED), "id");
    }
    public function allActiveForList($includeDisabled = true)
    {
        if (!is_bool($includeDisabled)) {
            throw new \InvalidArgumentException("\$includeDisabled must be a boolean");
        }
        $sortGroups = \Core\Db\DataRepository::instance()->pluck(\Component\Groups\Model\Group::definition(), "type = " . \Core\Db\Db::quote(\Component\Groups\Model\Group::TYPE_CAMPAIGN), "id", "position asc");
        if (!empty($sortGroups)) {
            $order = "group_id IS NULL, FIELD(group_id, " . implode(",", $sortGroups) . "), position, id";
        } else {
            $order = "position, id";
        }
        return $includeDisabled ? $this->allNotDeleted(NULL, $order) : $this->allActive(NULL, $order);
    }
    public function allWithStats($params, \Component\Grid\QueryParams\UserParams $userParams)
    {
        $factory = new \Component\EntityGrid\EntityGridFactory();
        return $factory->userParams($userParams)->params($params)->setEntityDefinition($this->definition())->build();
    }
    public function allDeleted()
    {
        $campaigns = $this->all("state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED), "position, id");
        $items = [];
        foreach ($campaigns as $campaign) {
            $items[] = $campaign;
        }
        return $items;
    }
    public function listAsOptions($campaigns, $key = "id", $addBlank = false)
    {
        $items = [];
        if (empty($key)) {
            $key = "id";
        }
        if ($addBlank) {
            $items[] = [$key => "", "name" => \Core\Locale\LocaleService::t("campaigns.choose")];
        }
        foreach ($campaigns as $campaign) {
            $groupName = \Component\Groups\Repository\GroupsRepository::instance()->getName($campaign->getGroupId());
            $groupName = is_null($groupName) ? \Core\Locale\LocaleService::t("groups.default") : $groupName;
            $item = ["id" => $campaign->getId(), "name" => $campaign->getName(), "group_id" => $campaign->get("group_id"), "group" => $groupName];
            $item["value"] = (int) $campaign->get($key);
            $items[] = $item;
        }
        return $items;
    }
    public function getCostTypes()
    {
        $result = [];
        foreach ($this->_costTypes as $mode) {
            $result[] = ["value" => $mode, "name" => \Core\Locale\LocaleService::t("campaigns.cost_types." . $mode)];
        }
        return $result;
    }
    public function getBindVisitorTypes()
    {
        $result = [];
        foreach ($this->_bindVisitorTypes as $mode) {
            $result[] = ["value" => $mode, "name" => \Core\Locale\LocaleService::t("campaigns.bind_visitor_types." . $mode)];
        }
        return $result;
    }
    public function allByIds($campaignIds)
    {
        $campaigns = [];
        if (count($campaignIds)) {
            $campaigns = $this->all("id in (" . implode(",", \Core\Db\Db::quote($campaignIds)) . ")", "position, id");
        }
        return $campaigns;
    }
    public function findByGroupIds($groupIds)
    {
        $groupId = \Core\Db\Db::quote($groupId);
        $rows = $this->rawRows("id", "group_id in (" . implode(",", $groupIds) . ")");
        $result = [];
        foreach ($rows as $row) {
            $result[] = $row["id"];
        }
        return $result;
    }
    public function getDomainCountsDic()
    {
        $select = "domain_id, COUNT(*) as cnt";
        $groupBy = "domain_id";
        $rows = $this->rawRows($select, NULL, NULL, NULL, NULL, $groupBy);
        $dic = [];
        foreach ($rows as $row) {
            $dic[$row["domain_id"]] = $row["cnt"];
        }
        return $dic;
    }
    public function checkTrialCampaign($createCount)
    {
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        $exist = $this->count($where);
        return $exist + $createCount <= 2;
    }
    public function getParameterAliases(\Traffic\Model\Campaign $campaign)
    {
        $result = [];
        $campaignParams = $campaign->getParameters();
        if ($campaignParams && count($campaignParams)) {
            foreach ($campaignParams as $parameter => $data) {
                $alias = \Component\TrafficSources\Service\TrafficSourceService::instance()->getAliasForParameter($campaignParams, $parameter);
                if (!empty($alias)) {
                    $result[] = ["parameter" => $parameter, "alias" => $alias];
                }
            }
        }
        return $result;
    }
    public function findParameterKey($campaignId, $param)
    {
        $campaign = $this->findFirst("id = " . (int) $campaignId);
        if ($campaign) {
            $parameters = $campaign->get("parameters");
            if (isset($parameters)) {
                foreach ($parameters as $key => $parameter) {
                    if ($parameter["placeholder"] === $param) {
                        return $key;
                    }
                }
            }
        }
        return false;
    }
}

?>