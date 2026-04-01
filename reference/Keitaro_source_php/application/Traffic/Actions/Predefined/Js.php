<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class Js extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 2;
    protected function _execute()
    {
        $this->_executeInContext();
    }
    protected function _executeDefault()
    {
        $url = $this->getActionPayload();
        $this->setDestinationInfo($url);
        $js = $this->_getJavascriptRedirect($url);
        $code = "<html>\n        <head>\n            <script type=\"application/javascript\">" . $js . "</script>\n        </head>\n        <body>\n            The Document has moved <a href=\"" . $url . "\">here</a>\n        </body>\n        </html>";
        $this->setContent($code);
    }
    protected function _executeForScript()
    {
        $url = $this->getActionPayload();
        $this->setDestinationInfo($url);
        $this->setContentType("application/javascript");
        $js = $this->_getJavascriptRedirect($url);
        $this->setContent($js);
    }
    protected function _executeForFrame()
    {
        $url = $this->getActionPayload();
        $this->setDestinationInfo($url);
        $this->setContent(\Traffic\Actions\Service\RedirectService::instance()->frameRedirect($url));
    }
    private function _getJavascriptRedirect($url)
    {
        $code = "\n                function process() {\n                   if (window.location !== window.parent.location ) {\n                      top.location = \"" . $url . "\";\n                   } else {\n                      window.location = \"" . $url . "\";\n                   }\n                }\n                window.onerror = process;\n                process();";
        return $code;
    }
}

?>