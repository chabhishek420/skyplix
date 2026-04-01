<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Context;

class RefreshLicenseContext implements \Core\Context\ContextInterface
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
        $trustedIps = \Core\Security\ServerFinderService::instance()->getTrustedIps();
        if (in_array($serverRequest->getServerParam(\Traffic\Request\ServerRequest::REMOTE_ADDR), $trustedIps)) {
            \Core\Application\EssentialService::instance()->refreshToken();
            $result = "ok:" . \Core\Application\TsService::instance()->getTimestamp();
        } else {
            $result = "IP is not trusted";
        }
        $response = \Traffic\Response\Response::build(["disable_cache" => true, "body" => $result]);
        return new \Core\Dispatcher\SimpleDispatcher($response);
    }
    public function shutdown()
    {
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        return \Traffic\Response\Response::buildJson(["status" => 500, "body" => ["error" => "Error"]]);
    }
}

?>