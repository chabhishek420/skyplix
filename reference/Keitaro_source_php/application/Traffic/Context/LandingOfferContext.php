<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Context;

class LandingOfferContext implements \Core\Context\ContextInterface
{
    public function bootstrap()
    {
        \Core\Application\Bootstrap::initClickContext();
    }
    public function modifyRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        return $serverRequest;
    }
    public function dispatcher(\Traffic\Request\ServerRequest $serverRequest)
    {
        $token = $this->_getToken($serverRequest);
        if (!empty($token) && !strstr($token, \Traffic\LpToken\Service\LpTokenService::UUID_PREFIX)) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("Someone sent incorrect uuid token: " . $token);
            return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(441, "Page is unavailable"));
        }
        \Traffic\Logging\Service\TrafficLoggerService::instance()->entry()->add("[LandingOfferContext]");
        if (empty($token)) {
            \Traffic\Logging\Service\TrafficLoggerService::instance()->entry()->add("Warning! Param '_token' is empty. Running campaign assigned to the domain as new visit.");
            return new \Traffic\Dispatcher\ClickDispatcher();
        }
        $rawClick = $this->_restoreRawClick($token);
        if (empty($rawClick)) {
            return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse(422, "Failed to restore rawClick by uuid " . $token));
        }
        return new \Traffic\Dispatcher\LandingOfferDispatcher($rawClick);
    }
    public function shutdown()
    {
        \Traffic\Logging\Service\TrafficLoggerService::instance()->flush();
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        return \Component\CommonErrorHandler\CommonErrorHandler::handleAny($e);
    }
    private function _getToken(\Traffic\Request\ServerRequest $serverRequest)
    {
        $token = $serverRequest->getQueryParam("_token");
        if (!empty($token)) {
            return $token;
        }
        $token = \Traffic\Cookies\Service\CookiesService::instance()->getRaw($serverRequest, \Traffic\Pipeline\Stage\GenerateTokenStage::TOKEN_PARAM);
        if (!empty($token)) {
            \Traffic\Logging\Service\TrafficLoggerService::instance()->info("The token '" . $token . "' found in cookies");
            return $token;
        }
        return $token;
    }
    private function _errorResponse($status, $message)
    {
        \Traffic\Logging\Service\LoggerService::instance()->info($message);
        $error = "Sorry. The link is outdated or incorrect. Please read page Maintenance > Logs.";
        return \Traffic\Response\Response::build()->withStatus($status)->withHeader(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::HTML)->withBody(\Traffic\Response\ResponseFactory::safeBody($error));
    }
    private function _restoreRawClick($token)
    {
        $rawClick = $this->_restoreByToken($token);
        if (!empty($rawClick)) {
            return $rawClick;
        }
        return $this->_findInMySQL($token);
    }
    private function _restoreByToken($token)
    {
        return \Traffic\LpToken\Service\LpTokenService::instance()->getRawClickByToken($token);
    }
    private function _findInMySQL($token)
    {
        if (!\Core\Db\Db::instance()->isEnabled()) {
            return NULL;
        }
        $subId = \Traffic\LpToken\Service\LpTokenService::instance()->subIdFromToken($token);
        if (empty($subId)) {
            return NULL;
        }
        return \Component\Clicks\Repository\RawClickRepository::instance()->findBySubId($subId);
    }
}

?>