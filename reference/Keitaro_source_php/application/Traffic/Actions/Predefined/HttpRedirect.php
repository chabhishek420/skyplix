<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class HttpRedirect extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 1;
    protected function _execute()
    {
        $url = $this->getActionPayload();
        $this->addHeader("Location: " . $url);
        if ($this->getServerRequest()->getParam("kversion")) {
            if ($this->getServerRequest() && 0 <= version_compare($this->getServerRequest()->getParam("kversion"), "3.4")) {
                $this->setStatus(302);
            }
        } else {
            $this->setStatus(302);
        }
        $this->setDestinationInfo($url);
    }
}

?>