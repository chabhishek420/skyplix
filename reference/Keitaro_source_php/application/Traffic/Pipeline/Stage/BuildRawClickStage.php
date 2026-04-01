<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class BuildRawClickStage
{
    protected $_stages = NULL;
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $request = $payload->getServerRequest();
        $rawClick = $payload->getRawClick();
        if (empty($request)) {
            throw new StageException("Empty request");
        }
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        $this->_prepare($request, $rawClick, $logEntry);
        $this->_findLanguage($request, $rawClick, $logEntry);
        $this->_findOtherParams($request, $rawClick);
        $this->_findSeReferrer($request, $rawClick);
        $this->_findReferrer($request, $rawClick);
        $this->_findSource($request, $rawClick);
        $this->_findXRequestedWith($request, $rawClick);
        $this->_findSearchEngine($request, $rawClick);
        $this->_findKeyword($request, $rawClick);
        $this->_findDefaultKeyword($request, $rawClick);
        $this->_findCosts($request, $rawClick);
        $this->_findSubIds($request, $rawClick);
        $this->_findExtraParams($request, $rawClick);
        $this->_findIpInfo($request, $rawClick);
        $this->_findDeviceInfo($rawClick);
        $this->_checkIfBot($request, $rawClick, $logEntry);
        $this->_checkIfProxy($request, $rawClick, $logEntry);
        $payload->setRawClick($rawClick);
        return $payload;
    }
    private function _prepare(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick, \Traffic\Logging\TrafficLogEntry $logger)
    {
        if (!$rawClick->getDateTime()) {
            $rawClick->set("datetime", new \DateTime());
        }
        if (!$rawClick->getUserAgent()) {
            $rawClick->setUserAgent($request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_USER_AGENT));
        }
        if (!$rawClick->getIpString() || $rawClick->getIpString() === "0.0.0.0") {
            $rawClick->setIpString(\Traffic\Device\Service\RealRemoteIpService::instance()->find($request));
            $logMessage = $this->_getIpsHeadersString($request);
            $logger->add($logMessage);
        }
    }
    private function _checkIfBot(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick, \Traffic\Logging\TrafficLogEntry $logger)
    {
        if ($rawClick->get(\Traffic\GeoDb\IpInfoType::BOT_TYPE)) {
            $rawClick->setBot(true);
        } else {
            if (!$rawClick->isBot()) {
                $status = \Component\BotDetection\Service\UserBotListService::instance()->isBot($rawClick->getUserAgent(), $rawClick->getIpString(), ["check_bot_ip" => \Traffic\Repository\CachedSettingsRepository::instance()->get("check_bot_ip"), "check_bot_ua" => \Traffic\Repository\CachedSettingsRepository::instance()->get("check_bot_ua"), "check_bot_referer" => \Traffic\Repository\CachedSettingsRepository::instance()->get("check_bot_referer"), "check_bot_empty_ua" => \Traffic\Repository\CachedSettingsRepository::instance()->get("check_bot_empty_ua")], $logger);
                $rawClick->setBot($status);
            }
        }
    }
    private function _checkIfProxy(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick, \Traffic\Logging\TrafficLogEntry $logger)
    {
        if (\Traffic\GeoDb\Repository\GeoDbRepository::instance()->isDataTypeAvailable(\Traffic\GeoDb\IpInfoType::PROXY_TYPE) && ($proxyType = $rawClick->get(\Traffic\GeoDb\IpInfoType::PROXY_TYPE))) {
            $rawClick->setIsUsingProxy(true);
            $logger->add("Proxy detected: " . $proxyType);
        } else {
            if (\Traffic\Device\Service\ProxyService::instance()->usingProxy($request)) {
                $rawClick->setIsUsingProxy(true);
                $logger->add("WebProxy detected");
            }
        }
    }
    private function _findIpInfo(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        $info = \Traffic\Device\Service\IpInfoService::instance()->getIpInfo($rawClick->getIpString());
        foreach ($info as $dataType => $value) {
            $rawClick->set($dataType, $value);
        }
        $rawClick->set("is_geo_resolved", true);
        $rawClick->set("is_isp_resolved", true);
    }
    private function _findDeviceInfo(\Traffic\RawClick $rawClick)
    {
        $info = \Traffic\Device\Service\DeviceInfoService::instance()->info($rawClick->getUserAgent());
        foreach ($info as $dataType => $value) {
            $rawClick->set($dataType, $value);
        }
        if (!$rawClick->get(\Traffic\GeoDb\IpInfoType::CONNECTION_TYPE) && $rawClick->get(\Traffic\Device\DeviceInfoType::DEVICE_TYPE) == \Traffic\Device\DeviceType::DEVICE_MOBILE) {
            $rawClick->set(\Traffic\GeoDb\IpInfoType::CONNECTION_TYPE, \Component\GeoDb\Repository\ConnectionTypesRepository::WIFI);
        }
        $rawClick->set("is_device_resolved", true);
    }
    private function _findLanguage(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick, \Traffic\Logging\TrafficLogEntry $logger)
    {
        if ($rawClick->getLanguage()) {
            return NULL;
        }
        $lang = $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_ACCEPT_LANGUAGE) ? substr($request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_ACCEPT_LANGUAGE), 0, 2) : "";
        $lang = strtoupper($lang);
        if (!\Component\Device\Repository\LanguagesRepository::instance()->isValid($lang)) {
            $logger->add("Invalid language: \"" . $lang . "\"\" from \"" . $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_ACCEPT_LANGUAGE) . "\"");
            $lang = "";
        }
        $rawClick->setLanguage($lang);
    }
    private function _findReferrer(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        if (\Traffic\Service\ConfigService::instance()->isReferrerRedefineAllowed()) {
            $referrer = $request->getParam("referrer");
            if (!empty($referrer)) {
                $rawClick->setReferrer(urldecode($referrer));
                return NULL;
            }
        }
        if (!$rawClick->getReferrer() && $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_REFERER)) {
            $rawClick->setReferrer(urldecode($request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_REFERER)));
        }
    }
    private function _findSeReferrer(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        if ($rawClick->getSeReferrer()) {
            return NULL;
        }
        $seRefferer = $request->getParam("se_referrer");
        if (!empty($seRefferer)) {
            $seReferrer = urldecode($seRefferer);
            $rawClick->setSeReferrer($seReferrer);
        }
    }
    private function _findSource(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        if ($rawClick->getSource()) {
            return NULL;
        }
        if ($request->hasParam("source")) {
            $rawClick->setSource($request->getParam("source"));
        } else {
            if (!$rawClick->getSource() && $rawClick->getReferrer() && preg_match("/http[s]?:\\/\\/(.*?)\\//si", $rawClick->getReferrer(), $matches)) {
                $rawClick->setSource($matches[1]);
            }
        }
    }
    private function _findXRequestedWith(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        if ($rawClick->getXRequestedWith()) {
            return NULL;
        }
        $value = $request->getHeaderLine("X-Requested-With");
        if (trim($value)) {
            $rawClick->setXRequestedWith($value);
        }
    }
    private function _findKeyword(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        if ($rawClick->getKeyword()) {
            return NULL;
        }
        $keyword = $request->getParam("keyword");
        if (!empty($keyword)) {
            $keyword = urldecode($keyword);
        }
        $charset = \Traffic\Service\ConfigService::instance()->get("system", "keywords_charset", "utf-8");
        if ($request->hasParam("charset")) {
            $charset = $request->getParam("charset");
        }
        if (isset($keyword)) {
            $encodedKeyword = @iconv($charset, "UTF-8", $keyword);
            if ($encodedKeyword) {
                $keyword = $encodedKeyword;
            }
        }
        if (isset($keyword)) {
            $rawClick->setKeyword($keyword);
        }
        if ($rawClick->getSeReferrer() && !$rawClick->getKeyword()) {
            $keyword = \Traffic\Device\Service\ReferrerParserService::instance()->parse($rawClick->getSeReferrer());
            if ($keyword) {
                $rawClick->setKeyword($keyword);
            }
        }
        if ($rawClick->getReferrer() && !$rawClick->getKeyword()) {
            $keyword = \Traffic\Device\Service\ReferrerParserService::instance()->parse($rawClick->getReferrer());
            if ($keyword) {
                $rawClick->setKeyword($keyword);
            }
        }
    }
    private function _findDefaultKeyword(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        if (!$rawClick->getKeyword() && $request->getParam("default_keyword")) {
            $keyword = urldecode($request->getParam("default_keyword"));
            if ($request->getParam("charset")) {
                $encodedKeyword = @iconv(@$request->getParam("charset"), "UTF-8", $keyword);
                if ($encodedKeyword) {
                    $keyword = $encodedKeyword;
                }
            }
            if (isset($keyword)) {
                $rawClick->setKeyword($keyword);
            }
        }
        return $this;
    }
    private function _findSearchEngine(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        if ($rawClick->getSearchEngine()) {
            return NULL;
        }
        $se = $request->getParam("se");
        if (!empty($se)) {
            $rawClick->setSearchEngine(urldecode($se));
        }
        if (!$rawClick->getSearchEngine()) {
            $vars = @parse_url(@$rawClick->getSeReferrer());
            $searchEngine = isset($vars["host"]) ? $vars["host"] : NULL;
            if ($searchEngine) {
                $rawClick->setSearchEngine($searchEngine);
            }
        }
    }
    private function _findCosts(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        $cost = $request->getParam("cost");
        if (!is_null($cost)) {
            $rawClick->set("cost", \Traffic\Tools\Tools::utf8ize($cost));
        }
        $currency = $request->getParam("currency");
        if (!is_null($currency)) {
            $rawClick->set("currency", \Traffic\Tools\Tools::utf8ize($currency));
        }
    }
    private function _findSubIds(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        for ($i = 1; $i <= \Traffic\Model\Click::getSubIdCount(); $i++) {
            $subId = $request->getParam("sub_id_" . $i);
            if (!is_null($subId)) {
                $rawClick->setSubIdN($i, trim(urldecode($subId)));
            }
            $subId = $request->getParam("subid" . $i);
            if (!is_null($subId)) {
                $rawClick->setSubIdN($i, trim(urldecode($subId)));
            }
        }
    }
    private function _findExtraParams(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        for ($i = 1; $i <= \Traffic\Model\Click::EXTRA_PARAM_COUNT; $i++) {
            $extraParam = $request->getParam("extra_param_" . $i);
            if (!empty($extraParam)) {
                $rawClick->setExtraParam($i, trim(urldecode($extraParam)));
            }
        }
    }
    private function _findOtherParams(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        if ($request->getParam("lp_id")) {
            $rawClick->set("landing_id", $request->getParam("lp_id"));
        } else {
            if ($request->getParam("landing_url")) {
                $rawClick->setLandingUrl($request->getParam("landing_url"));
            }
        }
        $params = ["landing_id", "creative_id", "ad_campaign_id", "external_id"];
        foreach ($params as $paramName) {
            $paramValue = $request->getParam($paramName);
            if (!empty($paramValue)) {
                $rawClick->set($paramName, $paramValue);
            }
        }
    }
    private function _getIpsHeadersString(\Traffic\Request\ServerRequest $request)
    {
        $xffHeader = $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR);
        $cfcipHeader = $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_CF_CONNECTING_IP);
        $xripHeader = $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_X_REAL_IP);
        return "Possible IP headers: " . \Traffic\Request\ServerRequest::HEADER_X_FORWARDED_FOR . ": " . ($xffHeader ?: "Empty") . "; " . \Traffic\Request\ServerRequest::HEADER_CF_CONNECTING_IP . ": " . ($cfcipHeader ?: "Empty") . "; " . \Traffic\Request\ServerRequest::HEADER_X_REAL_IP . ": " . ($xripHeader ?: "Empty");
    }
}

?>