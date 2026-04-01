<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Context;

class TestContext implements ContextInterface
{
    public function bootstrap()
    {
        \Core\Application\Bootstrap::initTestContext();
        \Core\Application\Bootstrap::enableDebugErrors();
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
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        return \Traffic\Response\Response::build();
    }
}

?>