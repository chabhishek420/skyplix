<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class ShowText extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 100;
    public function getType()
    {
        return TYPE_OTHER;
    }
    public function getField()
    {
        return TEXT;
    }
    public function _execute()
    {
        $this->setContentType("text/plain; charset=utf-8");
        $this->_executeInContext();
        $this->setDestinationInfo(\Traffic\Actions\Service\StreamActionService::instance()->truncateDestination($this->getResponse()->getBody()));
    }
    public function _executeForScript()
    {
        $this->setContentType("application/javascript");
        $this->setContent($this->buildContent());
    }
    public function _executeForFrame()
    {
        $this->setContent($this->buildContent());
    }
    public function _executeDefault()
    {
        $this->setContent($this->buildContent());
    }
    private function buildContent()
    {
        return $this->processMacros($this->getPipelinePayload()->getActionPayload());
    }
}

?>