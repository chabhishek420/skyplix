<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Service;

class TPICampaignAssociationService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\ThirdPartyIntegration\Model\TPICampaignAssociation::definition();
    }
    public function updateCampaign($id, $campaign_id)
    {
        $integration = \Component\ThirdPartyIntegration\Repository\TPICampaignAssociationRepository::instance()->findByIntegrationAndCampaign($id, $campaign_id);
        if (empty($integration)) {
            $integration = new \Component\ThirdPartyIntegration\Model\TPICampaignAssociation();
        }
        $data = ["integration_id" => $id, "campaign_id" => $campaign_id];
        $integration->setData($data);
        $dataService = \Core\Db\DataService::instance();
        if ($integration->getId()) {
            if ($id == 0) {
                $dataService->delete($this->definition(), $integration);
            } else {
                $dataService->save($this->definition(), $integration);
            }
        } else {
            $dataService->create($this->definition(), $integration);
        }
        return $integration;
    }
}

?>