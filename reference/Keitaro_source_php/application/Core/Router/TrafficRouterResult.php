<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Router;

class TrafficRouterResult
{
    private $_serverRequest = NULL;
    private $_context = NULL;
    public function __construct(\Traffic\Request\ServerRequest $serverRequest, \Core\Context\ContextInterface $context)
    {
        return $this->setServerRequest($serverRequest)->setContext($context);
    }
    public function setServerRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        $this->_serverRequest = $serverRequest;
        return $this;
    }
    public function serverRequest()
    {
        return $this->_serverRequest;
    }
    public function setContext(\Core\Context\ContextInterface $context)
    {
        $this->_context = $context;
        return $this;
    }
    public function context()
    {
        return $this->_context;
    }
}

?>