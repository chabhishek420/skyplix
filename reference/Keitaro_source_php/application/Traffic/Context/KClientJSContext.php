<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Context;

class KClientJSContext implements \Core\Context\ContextInterface
{
    const CAMPAIGN_NOT_FOUND = "Campaign not found";
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
        return $serverRequest;
    }
    public function dispatcher(\Traffic\Request\ServerRequest $serverRequest)
    {
        \Traffic\Logging\Service\TrafficLoggerService::instance()->entry()->add("[KClientJSContext]");
        $pipelinePayload = new \Traffic\Pipeline\Payload(["raw_click" => new \Traffic\RawClick(), "server_request" => $serverRequest, "token_needed" => true]);
        $pipelinePayload->setForceRedirectOffer(true);
        return new \Traffic\Dispatcher\KClientJSDispatcher($pipelinePayload, 2);
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