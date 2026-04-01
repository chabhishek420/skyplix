<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Controller;

class TPIMandatoryController extends \Admin\Controller\BaseController
{
    public function listAsOptionsAction()
    {
        if (\Core\Application\FeatureService::instance()->isBasic()) {
            throw new \Core\Validator\ValidationError(["type" => [\Core\Locale\LocaleService::t("third_party_integration.pro_error")]]);
        }
        $integration = $this->getParam("integration");
        $result = [];
        if (isset($integration)) {
            $data = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->findByIntegrationName($integration);
            $result[] = ["value" => 0, "name" => \Core\Locale\LocaleService::t("third_party_integration.get_cost_default")];
            foreach ($data as $datum) {
                $result[] = ["value" => $datum->getId(), "name" => $datum->getIntegrationName()];
            }
        }
        return $result;
    }
    public function addCampaignAction()
    {
        $integrationId = $this->getParam("integration_id");
        $campaignId = $this->getParam("campaign_id");
        try {
            \Component\ThirdPartyIntegration\Service\TPICampaignAssociationService::instance()->updateCampaign($integrationId, $campaignId);
            return ["success" => true];
        } catch (\Exception $e) {
            return ["success" => false];
        }
    }
    public function removeCampaignAction()
    {
        $integrationId = $this->getParam("integration_id");
        $campaignId = $this->getParam("campaign_id");
        try {
            $integration = \Component\ThirdPartyIntegration\Repository\TPICampaignAssociationRepository::instance()->findByIntegrationAndCampaign($integrationId, $campaignId);
            \Component\ThirdPartyIntegration\Service\TPICampaignAssociationService::instance()->delete($integration);
            return ["success" => true];
        } catch (\Exception $e) {
            return ["success" => false];
        }
    }
    public function allAction()
    {
        $integrationId = $this->getParam("integration_id");
        $integrations = \Component\ThirdPartyIntegration\Repository\TPICampaignAssociationRepository::instance()->findByIntegrationId($integrationId);
        $groupIds = [];
        foreach ($integrations as $integration) {
            $groupIds[] = $integration->get("campaign_id");
        }
        $addBlank = filter_var($this->getParam("add_blank"), FILTER_VALIDATE_BOOLEAN);
        $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allByIds($groupIds);
        $campaigns = \Component\Users\Service\AclService::instance()->filterByAcl($campaigns, false, $this->getUser());
        return \Component\Campaigns\Repository\CampaignRepository::instance()->listAsOptions($campaigns, $this->getParam("key"), $addBlank);
    }
}

?>