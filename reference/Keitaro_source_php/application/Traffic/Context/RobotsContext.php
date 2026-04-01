<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Context;

class RobotsContext implements \Core\Context\ContextInterface
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
        return new \Traffic\Dispatcher\RobotsDispatcher();
    }
    public function shutdown()
    {
    }
    public function handleException(\Exception $e, \Traffic\Request\ServerRequest $serverRequest)
    {
        return \Component\CommonErrorHandler\CommonErrorHandler::handleAny($e);
    }
}

?>