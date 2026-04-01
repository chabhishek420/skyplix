<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Grid;

class AccessRestriction
{
    private $_allowedCampaignIds = NULL;
    public function __construct($allowedCampaignIds)
    {
        $this->_allowedCampaignIds = $allowedCampaignIds;
    }
    public function getFilter()
    {
        $ids = $this->_allowedCampaignIds;
        if ($ids == \Component\Users\Service\AclService::ALLOW_ANY) {
            return NULL;
        }
        if ($ids == \Component\Users\Service\AclService::ALLOW_NONE) {
            $ids = [-1];
        }
        return ["name" => "campaign_id", "operator" => \Component\Grid\Query\FilterItem::IN_LIST, "expression" => $ids];
    }
}

?>