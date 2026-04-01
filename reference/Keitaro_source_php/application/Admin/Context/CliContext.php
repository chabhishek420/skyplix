<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Context;

class CliContext implements \Core\Context\ContextInterface
{
    public function bootstrap()
    {
        \Core\Application\Bootstrap::enableDebugErrors();
        \Core\Application\Bootstrap::initCliContext();
    }
    public function modifyRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        return $serverRequest;
    }
    public function dispatcher(\Traffic\Request\ServerRequest $serverRequest)
    {
        return new \Core\Dispatcher\SimpleDispatcher(\Traffic\Response\Response::build());
    }
    public function shutdown()
    {
        \Core\EntityEventManager\Service\EntityEventService::instance()->emitEvents();
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        if ($e instanceof \Core\Application\Exception\LicenseError) {
            $response = \Traffic\Response\Response::build(["body" => $e->getMessage() . "\n" . $e->getTraceAsString()]);
        } else {
            $response = \Traffic\Response\Response::build(["body" => "Error: " . $e->getMessage()]);
        }
        return $response;
    }
}

?>