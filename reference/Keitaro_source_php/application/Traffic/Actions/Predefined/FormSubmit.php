<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class FormSubmit extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 5;
    protected $_delay = 0;
    protected function _execute()
    {
        $content = "<!doctype html>" . PHP_EOL;
        $content .= "<head>" . PHP_EOL;
        $content .= "<script>window.onload = function(){\n                setTimeout(function() {\n                    document.forms[0].submit();\n                }, " . $this->_delay * 1000 . ");\n            };</script>" . PHP_EOL;
        $content .= "</head><body>" . PHP_EOL;
        $content .= "<form action=\"" . $this->getActionPayload() . "\" method=\"POST\">";
        foreach ($this->getServerRequest()->getParsedBody() as $name => $value) {
            $content .= "<input type=\"hidden\" name=\"" . $name . "\" value=\"" . $value . "\" />" . PHP_EOL;
        }
        $content .= "</form>" . PHP_EOL;
        $content .= "</body></html>" . PHP_EOL;
        $this->setContent($content);
    }
}

?>