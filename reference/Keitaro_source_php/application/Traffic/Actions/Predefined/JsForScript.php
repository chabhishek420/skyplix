<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class JsForScript extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 900;
    protected function _execute()
    {
        $this->_executeInContext();
    }
    protected function _executeForScript()
    {
        $this->setContentType("application/javascript");
        $url = $this->getActionPayload();
        $this->setDestinationInfo($url);
        $this->setContent(\Traffic\Actions\Service\RedirectService::instance()->scriptRedirect($url));
    }
    protected function _executeForFrame()
    {
        $this->setContentType("html/text");
        $url = $this->getActionPayload();
        $this->setDestinationInfo($url);
        $this->setContent(\Traffic\Actions\Service\RedirectService::instance()->frameRedirect($url));
    }
    protected function _executeDefault()
    {
        $this->_executeForScript();
    }
}

?>