<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Repository;

class CampaignPostbackRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Campaigns\Model\CampaignPostback::definition();
    }
    public function getCampaignPostbacks(\Traffic\Model\Campaign $campaign)
    {
        $where = "campaign_id = " . \Core\Db\Db::quote($campaign->getId());
        return $this->all($where);
    }
}

?>