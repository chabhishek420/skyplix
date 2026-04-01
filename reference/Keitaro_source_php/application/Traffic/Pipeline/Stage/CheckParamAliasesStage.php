<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class CheckParamAliasesStage implements StageInterface
{
    private $_paramsWithAliases = ["se_referrer", "source", "keyword", "se", "landing_id", "creative_id", "ad_campaign_id", "external_id", "cost", "currency"];
    const SITE = "site";
    public function __construct()
    {
        for ($i = 1; $i <= \Traffic\Model\Click::getSubIdCount(); $i++) {
            $this->_paramsWithAliases[] = "sub_id_" . $i;
        }
        for ($i = 1; $i <= \Traffic\Model\Click::EXTRA_PARAM_COUNT; $i++) {
            $this->_paramsWithAliases[] = "extra_param_" . $i;
        }
    }
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $campaign = $payload->getCampaign();
        $rawClick = $payload->getRawClick();
        $request = $payload->getServerRequest();
        if (empty($campaign)) {
            throw new StageException("Empty campaign");
        }
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        if (empty($request)) {
            throw new StageException("Empty request");
        }
        $this->_checkAliasesFromSettings($request, $rawClick, $logEntry);
        $this->_checkAliasesFromCampaign($request, $rawClick, $campaign->getParameters(), $logEntry);
        $this->_checkSiteAlias($request, $rawClick, $campaign->getParameters(), $logEntry);
        $this->_checkPlaceholderFromCampaign($request, $rawClick, $campaign->getParameters(), $logEntry);
        return $payload;
    }
    protected function _checkAliasesFromSettings(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick, \Traffic\Logging\TrafficLogEntry $logger)
    {
        foreach ($this->_paramsWithAliases as $paramName) {
            $oldParam = $request->getParam($paramName);
            if (is_null($oldParam)) {
                foreach (\Traffic\Repository\ParameterRepository::instance()->getAliasesFor($paramName) as $alias) {
                    if ($request->hasParam($alias) && $paramName != $alias) {
                        $logger->add("Param alias matched " . $alias . " -> " . $paramName);
                        $rawClick->set($paramName, \Traffic\Tools\Tools::utf8ize($request->getParam($alias)));
                    }
                }
            }
        }
    }
    protected function _checkAliasesFromCampaign(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick, $parameters, \Traffic\Logging\TrafficLogEntry $logger)
    {
        if (empty($parameters)) {
            return NULL;
        }
        foreach ($parameters as $paramName => $valueArr) {
            $alias = !empty($valueArr["name"]) ? $valueArr["name"] : $paramName;
            if ($request->hasParam($alias) && $paramName != $alias) {
                $logger->add("Param alias matched " . $alias . " -> " . $paramName);
                $rawClick->set($paramName, \Traffic\Tools\Tools::utf8ize($request->getParam($alias)));
            }
        }
    }
    protected function _checkSiteAlias(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick, $parameters, \Traffic\Logging\TrafficLogEntry $logger)
    {
        if ($request->getParam(SITE)) {
            $rawClick->set("source", $request->getParam(SITE));
        }
    }
    protected function _isMacro($_isMacro, $value)
    {
        $firstSymbol = substr(trim($value), 0, 1);
        return in_array($firstSymbol, ["[", "{"]);
    }
    protected function _checkPlaceholderFromCampaign(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick, $parameters, \Traffic\Logging\TrafficLogEntry $logger)
    {
        if (empty($parameters)) {
            return NULL;
        }
        foreach ($parameters as $paramName => $valueArr) {
            if (!empty($valueArr["placeholder"])) {
                $placeholder = $valueArr["placeholder"];
                $alias = !empty($valueArr["name"]) ? $valueArr["name"] : $paramName;
                if (!$request->hasParam($alias) && !$request->hasParam($paramName) && !$rawClick->get($paramName) && !$this->_isMacro($placeholder)) {
                    $logger->add("No value provided for " . $paramName . ". Setting value from placeholder -> " . $placeholder);
                    $rawClick->set($paramName, $placeholder);
                }
            }
        }
    }
}

?>