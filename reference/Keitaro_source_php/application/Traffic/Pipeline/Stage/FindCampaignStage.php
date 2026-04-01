<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class FindCampaignStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $request = $payload->getServerRequest();
        if ($payload->getCampaign()) {
            return $payload;
        }
        if (empty($request)) {
            throw new StageException("Empty request");
        }
        if ($payload->getForcedCampaignId()) {
            $logEntry->add("[Restored] Processing campaign " . $payload->getForcedCampaignId());
            $campaign = \Traffic\Repository\CachedCampaignRepository::instance()->findInCacheById($payload->getForcedCampaignId());
            $payload->setForcedCampaignId(NULL);
            $payload->setCampaign($campaign);
            return $payload;
        }
        $logEntry->add("Requested: " . (int) $request->getUri());
        $logEntry->add("Searching campaign");
        $campaign = $this->_tryToFindCampaign($request);
        if (empty($campaign)) {
            $logEntry->add("Campaign is not found. Checking assigned to domain campaign.");
            $campaign = $this->_findDomainDefaultCampaign($request);
        }
        if (empty($campaign)) {
            return $payload;
        }
        if (!$campaign->isActive()) {
            $logEntry->add("Campaign is not active (" . $campaign->getState() . ")");
            return $payload;
        }
        $payload->setCampaign($campaign);
        return $payload;
    }
    private function _tryToFindCampaign(\Traffic\Request\ServerRequest $request)
    {
        $campaignAliases = $this->_getCampaignParamAliases($request);
        $campaign = NULL;
        foreach ($campaignAliases as $campaignAlias) {
            $campaign = \Traffic\Repository\CachedCampaignRepository::instance()->findByAlias($campaignAlias);
            if (empty($campaign) && \Traffic\Service\ConfigService::instance()->get("system", "allow_by_id")) {
                $campaign = \Traffic\Repository\CachedCampaignRepository::instance()->findInCacheById($campaignAlias);
            }
            if (!empty($campaign)) {
                return $campaign;
            }
        }
        return NULL;
    }
    private function _findDomainDefaultCampaign(\Traffic\Request\ServerRequest $request)
    {
        if (empty($campaign)) {
            $campaignId = \Traffic\Repository\CachedDomainRepository::instance()->getCampaignIdByUrl($request->getUri());
            if (!empty($campaignId)) {
                return \Traffic\Repository\CachedCampaignRepository::instance()->findInCacheById($campaignId);
            }
        }
    }
    private function _getCampaignParamAliases(\Traffic\Request\ServerRequest $request)
    {
        $result = [];
        $param = $request->getParam(\Core\Router\TrafficRouter::PARAM_CAMPAIGN);
        if (!empty($param)) {
            $result[] = $param;
        }
        $keys = \Traffic\Repository\ParameterRepository::instance()->getAliasesForId();
        foreach ($keys as $key) {
            $param = $request->getParam($key);
            if (!empty($param)) {
                $result[] = $param;
            }
        }
        $params = $request->getQueryParams();
        if (!empty($params)) {
            $keys = array_keys($params);
            $result = array_merge($result, $keys);
        }
        return $result;
    }
}

?>