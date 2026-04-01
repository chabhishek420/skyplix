<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Dispatcher;

class SimpleDispatcher implements DispatcherInterface
{
    private $_response = NULL;
    public function __construct(\Traffic\Response\Response $response)
    {
        $this->_response = $response;
    }
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        return $this->_response;
    }
}

?>