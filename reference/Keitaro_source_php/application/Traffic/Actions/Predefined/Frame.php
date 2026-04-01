<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class Frame extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 200;
    public function getType()
    {
        return TYPE_REDIRECT;
    }
    protected function _execute()
    {
        $this->_executeInContext();
    }
    protected function _executeDefault()
    {
        $url = $this->getActionPayload();
        $this->setDestinationInfo($url);
        $this->setContent("<html>\n            <head><meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1\" /></head>\n            <frameset rows=\"100%\"><frame src=\"" . $url . "\"></frameset></html>");
    }
}

?>