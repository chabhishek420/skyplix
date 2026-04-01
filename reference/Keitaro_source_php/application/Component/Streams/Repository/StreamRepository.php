<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Repository;

class StreamRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\Model\Stream::definition();
    }
    public function allOrderedStreamsForCampaign(\Traffic\Model\Campaign $campaign, $conditions = NULL)
    {
        $order = [];
        $order[] = "FIELD(`type`, \"forced\", \"regular\", \"default\")";
        return $this->getCampaignStreams($campaign, $conditions, implode(", ", $order));
    }
    public function getCampaignStreams(\Traffic\Model\Campaign $campaign, $conditions = NULL, $order = NULL)
    {
        $where = [];
        $where[] = "campaign_id = " . \Core\Db\Db::quote($campaign->getId());
        $where[] = "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        if ($conditions) {
            $where[] = $conditions;
        }
        return $this->getStreams(implode(" AND ", $where), $order);
    }
    public function getStreams($conditions = NULL, $order = NULL)
    {
        return $this->all($conditions, $order);
    }
    public function allDeleted($opts = [])
    {
        $where = [];
        $where[] = "state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        if (!empty($opts["allowedCampaignIds"])) {
            $where[] = " AND campaign_id IN (" . implode(",", $opts["allowedCampaignIds"]) . ")";
        }
        return $this->getStreams(implode(" AND ", $where));
    }
    public function allDeletedBefore(\DateTime $date)
    {
        $where = [];
        $where[] = "state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        $where[] = "updated_at < " . \Core\Db\Db::quote($date->format(\Core\Model\AbstractModel::DATETIME_FORMAT));
        return $this->getStreams(implode(" AND ", $where));
    }
    public function getActiveCampaignStreams($exclude, $allowedCampaignIds)
    {
        $where = [];
        $where[] = "id <> " . (int) $exclude;
        $where[] = "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        $deletedCampaignIds = \Component\Campaigns\Repository\CampaignRepository::instance()->getDeletedCampaignIds();
        if (count($deletedCampaignIds)) {
            $where[] = "campaign_id NOT IN (" . implode(",", $deletedCampaignIds) . ")";
        }
        if (!empty($allowedCampaignIds)) {
            $where[] = "campaign_id IN (" . implode(",", $allowedCampaignIds) . ")";
        }
        return $this->getStreams(implode(" AND ", $where), "campaign_id, `position`");
    }
    public function getStreamsAsOptions($streams)
    {
        $items = [];
        foreach ($streams as $stream) {
            $name = $stream->getName();
            $items[] = ["id" => $stream->getId(), "group" => \Component\Campaigns\Repository\CampaignRepository::instance()->getNameById($stream->getCampaignId()), "name" => "[" . $stream->getId() . "] " . $name];
        }
        return $items;
    }
    public function getCampaignStreamsCount(\Traffic\Model\Campaign $campaign)
    {
        $conditions = [];
        $conditions[] = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        $conditions[] = "campaign_id = " . \Core\Db\Db::quote($campaign->getId());
        return StreamRepository::instance()->count(implode(" AND ", $conditions));
    }
    public function allActiveForTypeAndCampaign($type, \Traffic\Model\Campaign $campaign, $condition = NULL, $order = NULL)
    {
        if (empty($campaign)) {
            throw new \Core\Application\Exception\Error("Empty campaign");
        }
        if (empty($order)) {
            if ($campaign->get("type") == \Traffic\Model\Campaign::TYPE_POSITION) {
                $order = "position, updated_at";
            } else {
                $order = "id";
            }
        }
        $where = [];
        $where[] = "type = " . \Core\Db\Db::quote($type);
        $where[] = "campaign_id = " . (int) $campaign->getId();
        $where[] = "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        if (isset($condition)) {
            $where[] = $condition;
        }
        return $this->all(implode(" AND ", $where), $order);
    }
    public function checkTrialStream($campaignId, $wantToAddCount = 1)
    {
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        $where .= " AND campaign_id = " . \Core\Db\Db::quote($campaignId);
        $count = $this->count($where);
        return $count + $wantToAddCount <= $this->getMaxTrialStreams();
    }
    public function getMaxTrialStreams()
    {
        return 2;
    }
    public function getIdsForCampaignIds($ids)
    {
        $where = "campaign_id IN (" . implode(",", $ids) . ")";
        return $this->pluck($where, "id");
    }
    public function getActiveIdsForCampaignIds($ids)
    {
        $where = "campaign_id IN (" . implode(",", $ids) . ") and state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        return $this->pluck($where, "id");
    }
    public function getCampaignIdsForIds($ids)
    {
        $where = "id IN (" . implode(",", $ids) . ")";
        $campaignIds = $this->pluck($where, "campaign_id");
        return array_unique($campaignIds);
    }
}

?>