<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Context;

class ClickApiContext implements \Core\Context\ContextInterface
{
    private $_apiParams = ["language" => ["lang", "language"], "referrer" => ["referrer", "referer"], "user_agent" => ["ua", "user_agent"], "search_engine" => ["search_engine", "se"], "landing_id" => ["landing_id"], "datetime" => ["datetime"], "always_empty_cookies" => ["always_empty_cookies"]];
    const API_KEY = "api_key";
    const TOKEN_NAME = "token";
    const DEFAULT_VERSION = 1;
    const UNIQUENESS_COOKIE_PARAM = "uniqueness_cookie";
    public function bootstrap()
    {
        \Core\Application\Bootstrap::initClickContext();
        \Traffic\Logging\Service\TrafficLoggerService::reset();
        if (\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::TRAFFIC_LOG_ENABLED)) {
            \Traffic\Logging\Service\TrafficLoggerService::instance()->setEntry(new \Traffic\Logging\TrafficLogEntry());
        }
    }
    public function modifyRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        $serverRequest = $this->_setUniquenessCookie($serverRequest);
        $serverRequest = $this->_removeCloudFlareIpCountry($serverRequest);
        $serverRequest = $serverRequest->withHeaders([\Traffic\Request\ServerRequest::HEADER_X_REAL_IP => $serverRequest->getParam("ip"), \Traffic\Request\ServerRequest::HEADER_CF_CONNECTING_IP => $serverRequest->getParam("ip")]);
        $serverRequest = $serverRequest->withServerParams([\Traffic\Request\ServerRequest::REMOTE_ADDR => $serverRequest->getParam("ip")]);
        return $serverRequest;
    }
    public function dispatcher(\Traffic\Request\ServerRequest $serverRequest)
    {
        \Traffic\Logging\Service\TrafficLoggerService::instance()->entry()->add("[ClickApiContext]");
        \Traffic\Device\Service\ProxyService::instance()->forceNoProxy();
        $version = $this->_findVersion($serverRequest);
        $rawClick = new \Traffic\RawClick();
        $rawClick = $this->_replaceRawClickParams($serverRequest, $rawClick);
        $campaign = NULL;
        if ($this->_isApiKeyProvided($serverRequest)) {
            if (!$this->_isApiValid($serverRequest)) {
                return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(403, "Invalid token or api key"));
            }
        } else {
            $token = $this->_findToken($serverRequest);
            if (empty($token)) {
                return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(404, "No campaign token"));
            }
            $campaign = \Traffic\Repository\CachedCampaignRepository::instance()->findByToken($token);
            if (empty($campaign)) {
                return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(403, "Invalid campaign token '" . $token . "'"));
            }
        }
        if (!\Core\Application\FeatureService::instance()->hasClickApiFeature()) {
            return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(402, "Click API available only for PRO editions"));
        }
        $pipelinePayload = new \Traffic\Pipeline\Payload(["raw_click" => $rawClick, "server_request" => $serverRequest, "campaign" => $campaign]);
        if ($serverRequest->hasParam("uri")) {
            \Traffic\Logging\Service\TrafficLoggerService::instance()->entry()->add("Requested: " . $serverRequest->getParam("uri"));
        }
        $pipelinePayload = $this->_adaptPayloadByApiVersion($pipelinePayload, $version);
        return new \Traffic\Dispatcher\ClickApiDispatcher($pipelinePayload, $version);
    }
    public function shutdown()
    {
        \Traffic\Logging\Service\TrafficLoggerService::instance()->flush();
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        if ($e instanceof \Core\Application\Exception\LicenseError) {
            $body = ["error" => $e->getMessage(), "stacktrace" => $e->getTraceAsString()];
            \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage() . ": " . $e->getTraceAsString());
        } else {
            $body = ["error" => $e->getMessage()];
            \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
        }
        if (!\Core\Application\Application::instance()->isDebug()) {
            $body["error"] = \Component\CommonErrorHandler\CommonErrorHandler::GENERAL_ERROR_MESSAGE;
        }
        return \Traffic\Response\Response::buildJson(["status" => \Traffic\Response\StatusCode::INTERNAL_SERVER_ERROR, "body" => $body]);
    }
    private function _removeCloudFlareIpCountry(\Traffic\Request\ServerRequest $request)
    {
        return $request->withoutHeader(\Traffic\Request\ServerRequest::HEADER_CF_IPCOUNTRY);
    }
    private function _findToken(\Traffic\Request\ServerRequest $request)
    {
        return $request->getParam(TOKEN_NAME);
    }
    private function _replaceRawClickParams(\Traffic\Request\ServerRequest $request, \Traffic\RawClick $rawClick)
    {
        foreach ($this->_apiParams as $attr => $variations) {
            foreach ($variations as $param) {
                if ($request->hasParam($param)) {
                    $rawClick->set($attr, $request->getParam($param));
                }
            }
        }
        return $rawClick;
    }
    private function _isApiKeyProvided(\Traffic\Request\ServerRequest $request)
    {
        return $request->getParam(API_KEY) && \Traffic\Repository\CachedSettingsRepository::instance()->get(API_KEY);
    }
    private function _isApiValid(\Traffic\Request\ServerRequest $request)
    {
        return $request->getParam(API_KEY) === \Traffic\Repository\CachedSettingsRepository::instance()->get(API_KEY);
    }
    private function _errorResponse($status, $error)
    {
        return \Traffic\Response\Response::build()->withStatus($status)->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::JSON)->withBody(\Traffic\Response\ResponseFactory::safeBody(["error" => $error]));
    }
    private function _findVersion(\Traffic\Request\ServerRequest $request)
    {
        $version = $request->getParam(\Core\Router\TrafficRouter::PARAM_VERSION);
        if ($version && in_array($version, $this->_getVersions())) {
            return $version;
        }
        return DEFAULT_VERSION;
    }
    private function _setUniquenessCookie(\Traffic\Request\ServerRequest $serverRequest)
    {
        if ($serverRequest->hasParam(UNIQUENESS_COOKIE_PARAM)) {
            $serverRequest = $serverRequest->withCookieParam(\Traffic\Session\Storage\CookiesStorage::getCookieName(), $serverRequest->getParam(UNIQUENESS_COOKIE_PARAM));
        }
        return $serverRequest;
    }
    private function _adaptPayloadByApiVersion(\Traffic\Pipeline\Payload $payload, $version)
    {
        if ($version < 3 || $payload->getServerRequest()->getParam("force_redirect_offer")) {
            $payload->setForceRedirectOffer(true);
        } else {
            $payload->setForceRedirectOffer(false);
        }
        if ($version < 3 && $payload->getServerRequest()->getParam("force_choose_offer")) {
            $payload->setForceChooseOffer(true);
        }
        return $payload;
    }
    private function _getVersions()
    {
        return [1, 2, 3];
    }
}

?>