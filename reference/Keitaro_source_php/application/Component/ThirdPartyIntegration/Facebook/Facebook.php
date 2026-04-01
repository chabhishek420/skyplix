<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Facebook;

class Facebook
{
    private $_api = NULL;
    private $_cabinetInfo = NULL;
    private $_adsets = [];
    private $_insights = [];
    const INTEGRATION = "facebook";
    const FB_PARAMETER = "{{adset.id}}";
    public function syncCost($integration_id, $force = false)
    {
        $integration = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->find($integration_id);
        $this->_start($integration, $force);
    }
    public function syncCostsAll()
    {
        $integrations = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->findByIntegrationName(INTEGRATION);
        if ($integrations) {
            foreach ($integrations as $integration) {
                $this->_start($integration);
            }
        }
        return ["success" => true];
    }
    private function _start(\Component\ThirdPartyIntegration\Model\ThirdPartyIntegration $integration, $force = false)
    {
        $fbSettings = new FbIntegrationSettings($integration->getIntegrationParams());
        $fbSettings->setId($integration->getId());
        $campaignsAll = \Component\ThirdPartyIntegration\Repository\TPICampaignAssociationRepository::instance()->findByIntegrationId($fbSettings->getId());
        if ($campaignsAll) {
            foreach ($campaignsAll as $datum) {
                $fbSettings->setCampaigns($datum->get("campaign_id"));
            }
        }
        try {
            if ($this->_isReady($fbSettings) || $force) {
                $this->_api = new Api\Connect($fbSettings->getProxyEnabled(), $fbSettings->getProxy());
                $this->_loadData($fbSettings);
            }
        } catch (\Exception $e) {
            FacebookLog::error($fbSettings, "third_party_integration.errors.connection");
        }
    }
    private function _loadData(FbIntegrationSettings $integration)
    {
        try {
            $token = $this->_api->checkToken($integration->getToken());
            if ($token && $integration->getCampaigns()) {
                try {
                    $this->_updateCost($integration);
                    return ["success" => true];
                } catch (\Component\ThirdPartyIntegration\Exception\FbConnectionError $e) {
                    return ["success" => false];
                }
            }
            FacebookLog::error($integration, "third_party_integration.errors.campaign");
            return ["success" => false];
        } catch (\Component\ThirdPartyIntegration\Exception\FbConnectionError $e) {
            FacebookLog::error($integration, "third_party_integration.errors.token", $e->getMessage());
            return ["success" => false];
        }
    }
    private function _updateCost(FbIntegrationSettings $integration)
    {
        $this->_loadCabinetInfo($integration);
        $this->_loadAdsets($integration);
        $this->_loadInsights($integration);
        $this->_updateCampaignCost($integration);
    }
    private function _loadCabinetInfo(FbIntegrationSettings $integration)
    {
        try {
            $cabinetInfo = $this->_api->getCabinetInfo($integration->getAdAccountId(), $integration->getToken());
            $this->_cabinetInfo = $cabinetInfo;
        } catch (\Component\ThirdPartyIntegration\Exception\FbConnectionError $e) {
            FacebookLog::error($integration, "third_party_integration.errors.cabinet", $e->getMessage());
            throw $e;
        }
    }
    private function _loadAdsets(FbIntegrationSettings $integration)
    {
        try {
            $adsets = $this->_api->getAdsets($integration->getAdAccountId(), $integration->getToken());
            $this->_adsets = $adsets;
        } catch (\Component\ThirdPartyIntegration\Exception\FbConnectionError $e) {
            FacebookLog::error($integration, "third_party_integration.errors.adsets", $e->getMessage());
            throw $e;
        }
    }
    private function _loadInsights(FbIntegrationSettings $integration)
    {
        $first = true;
        if ($integration->getLastUpdate()) {
            $first = false;
        }
        foreach ($this->_adsets["data"] as $adset) {
            try {
                $insights = $this->_api->getInsights($adset["id"], $this->_cabinetInfo["timezone_offset_hours_utc"], $integration->getToken(), $first);
                if ($insights) {
                    $this->_insights[$adset["id"]] = $insights;
                }
            } catch (\Component\ThirdPartyIntegration\Exception\FbConnectionError $e) {
                FacebookLog::error($integration, "third_party_integration.errors.adsets", $e->getMessage());
                throw $e;
            }
        }
    }
    private function _updateCampaignCost(FbIntegrationSettings $integration)
    {
        $updates = [];
        foreach ($this->_insights as $adset => $insights) {
            foreach ($insights["data"] as $value) {
                $campaigns = [];
                $filters = [];
                foreach ($integration->getCampaigns() as $campaign) {
                    $sub_name = \Component\Campaigns\Repository\CampaignRepository::instance()->findParameterKey($campaign, FB_PARAMETER);
                    if ($sub_name) {
                        $campaigns[] = $campaign;
                        $filters[$sub_name][] = (int) $adset;
                    }
                }
                if (!empty($campaigns)) {
                    $data = new \Component\Campaigns\UseCase\UpdateCampaignCostPayload();
                    $data->setStartDate($value["date_start"] . " 00:00:00");
                    $data->setEndDate($value["date_start"] . " 23:59:59");
                    $data->setCost($value["spend"]);
                    $data->setCampaigns($campaigns);
                    $data->setFilters($filters);
                    $updates[] = $data;
                }
            }
        }
        if (!empty($updates)) {
            try {
                \Component\Campaigns\DelayedCommand\UpdateCostsBulkCommand::enqueue($updates, $this->_cabinetInfo["currency"], $this->_cabinetInfo["timezone_name"]);
            } catch (\Core\Validator\ValidationError $e) {
                FacebookLog::error($integration, "third_party_integration.errors.update");
            }
        }
        FacebookLog::success($integration);
        return ["success" => true];
    }
    private function _isReady(FbIntegrationSettings $fbSettings)
    {
        $lastUpdate = $fbSettings->getLastUpdate();
        $interval = $fbSettings->getInterval();
        if ($interval < 1) {
            return false;
        }
        if ($lastUpdate) {
            $next = 1440 / $interval * 60 + $lastUpdate;
            return $next - 300 <= time();
        }
        return true;
    }
}

?>