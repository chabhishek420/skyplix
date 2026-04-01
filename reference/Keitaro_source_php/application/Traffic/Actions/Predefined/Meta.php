<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class Meta extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 2;
    protected function _execute()
    {
        $this->setDestinationInfo($this->getActionPayload());
        $this->_executeInContext();
    }
    protected function _executeDefault()
    {
        $code = \Traffic\Actions\Service\RedirectService::instance()->metaRedirect($this->getActionPayload());
        $this->setContent($code);
    }
    protected function _executeForScript()
    {
        $code = \Traffic\Actions\Service\RedirectService::instance()->metaRedirect($this->getActionPayload());
        $this->setContentType("application/javascript");
        $parser = new \Traffic\Actions\AdsParser($code, $this->getServerRequest()->getParam("_cid"));
        $code = $parser->getCode();
        $this->setContent($code);
    }
}

?>