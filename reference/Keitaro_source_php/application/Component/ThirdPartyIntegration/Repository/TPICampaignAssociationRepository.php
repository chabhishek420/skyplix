<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Repository;

class TPICampaignAssociationRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\ThirdPartyIntegration\Model\TPICampaignAssociation::definition();
    }
    public function findByCampaignId($campaign_id)
    {
        $where = "campaign_id = " . \Core\Db\Db::quote($campaign_id);
        return $this->findFirst($where);
    }
    public function findIdByCampaignId($id)
    {
        $integration = $this->findByCampaignId($id);
        if ($integration) {
            return $integration->get("integration_id");
        }
        return NULL;
    }
    public function findByIntegrationId($integration_id)
    {
        $where = "integration_id = " . \Core\Db\Db::quote($integration_id);
        return $this->all($where);
    }
    public function findByIntegrationAndCampaign($integration_id, $campaign_id)
    {
        $where = "campaign_id = " . \Core\Db\Db::quote($campaign_id) . " AND integration_id = " . \Core\Db\Db::quote($integration_id);
        return $this->findFirst($where);
    }
}

?>