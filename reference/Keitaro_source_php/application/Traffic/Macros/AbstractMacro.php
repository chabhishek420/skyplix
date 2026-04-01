<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros;

abstract class AbstractMacro
{
    private $_serverRequest = NULL;
    public function alwaysRaw()
    {
        return false;
    }
    public function getServerRequest()
    {
        return $this->_serverRequest;
    }
    public function setServerRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        $this->_serverRequest = $serverRequest;
    }
}

?>