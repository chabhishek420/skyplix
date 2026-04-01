<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class Iframe extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 6;
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
        $html = "<!DOCTYPE html>\n        <html>\n        <head>\n        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1\" />\n        </head>   \n        <style type=\"text/css\">\n        body, html{\n            margin: 0;\n            padding: 0;\n            width: 100%;\n            height: 100%;\n            overflow-y: auto;\n            overflow-x: hidden;\n            -webkit-overflow-scrolling:touch\n        }\n        iframe {\n                width: 100%;\n                height:100%;\n                min-height: 10000px;\n                border: 0;\n            }\n        </style>\n        <body><iframe src=\"" . $url . "\"></iframe></body>\n        </html>";
        $this->setContent($html);
        $this->setDestinationInfo($url);
    }
    protected function _executeForFrame()
    {
        $url = $this->getActionPayload();
        $this->addHeader("Location: " . $url);
        if ($this->getServerRequest() && 0 <= version_compare($this->getServerRequest()->getParam("kversion"), "3.4")) {
            $this->setStatus(302);
        }
        $this->setDestinationInfo($url);
    }
}

?>