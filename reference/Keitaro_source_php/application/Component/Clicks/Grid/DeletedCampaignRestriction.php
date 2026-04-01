<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Grid;

class DeletedCampaignRestriction
{
    public function getDeletedCampaignIds()
    {
        return \Component\Campaigns\Repository\CampaignRepository::instance()->pluck("state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED), "id");
    }
    public function getFilter()
    {
        $ids = $this->getDeletedCampaignIds();
        if (count($ids)) {
            return ["name" => "campaign_id", "operator" => \Component\Grid\Query\FilterItem::NOT_IN_LIST, "expression" => $ids];
        }
        return NULL;
    }
}

?>