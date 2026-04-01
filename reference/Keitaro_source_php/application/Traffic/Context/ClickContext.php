<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Context;

class ClickContext implements \Core\Context\ContextInterface
{
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
        $serverRequest = $serverRequest->withServerParams(["REMOTE_ADDR" => \Traffic\Device\Service\RealRemoteIpService::instance()->find($serverRequest)]);
        return $serverRequest;
    }
    public function dispatcher(\Traffic\Request\ServerRequest $serverRequest)
    {
        \Traffic\Logging\Service\TrafficLoggerService::instance()->entry()->add("[ClickContext]");
        return new \Traffic\Dispatcher\ClickDispatcher();
    }
    public function shutdown()
    {
        \Traffic\Logging\Service\TrafficLoggerService::instance()->flush();
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        return \Component\CommonErrorHandler\CommonErrorHandler::handleAny($e);
    }
}

?>