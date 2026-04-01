<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Context;

class GatewayRedirectContext implements \Core\Context\ContextInterface
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
        $token = $serverRequest->getParam("token");
        if (empty($token)) {
            return new \Core\Dispatcher\SimpleDispatcher($this->_errorResponse("Empty token", 500));
        }
        return new \Traffic\Dispatcher\GatewayRedirectDispatcher();
    }
    public function shutdown()
    {
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        return \Component\CommonErrorHandler\CommonErrorHandler::handleAny($e);
    }
    private function _errorResponse($msg, $status = 500)
    {
        return \Traffic\Response\Response::buildHtml()->withStatus($status)->withBody(\Traffic\Response\ResponseFactory::safeBody($msg));
    }
}

?>