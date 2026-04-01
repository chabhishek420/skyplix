<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class ShowHtml extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 100;
    public function getField()
    {
        return TEXT;
    }
    public function getType()
    {
        return TYPE_OTHER;
    }
    protected function _execute()
    {
        $this->_executeInContext();
    }
    protected function _executeForScript()
    {
        $this->setContentType("application/javascript");
        $parser = new \Traffic\Actions\AdsParser($this->buildContent(), $this->getServerRequest()->getParam("_cid"));
        $code = $parser->getCode();
        $this->setDestinationInfo(\Traffic\Actions\Service\StreamActionService::instance()->truncateDestination($code));
        $this->setContent($code);
    }
    protected function _executeForFrame()
    {
        $this->setContentType("text/html");
        $code = $this->buildContent();
        if (!stristr($code, "<html")) {
            $code = "<html><style>body{margin:0}</style><body>" . $code . "</body></html>";
        }
        $this->setDestinationInfo(\Traffic\Actions\Service\StreamActionService::instance()->truncateDestination($code));
        $this->setContent($code);
    }
    protected function _executeDefault()
    {
        $this->_executeForFrame();
    }
    private function buildContent()
    {
        return $this->processMacros($this->getPipelinePayload()->getActionPayload());
    }
}

?>