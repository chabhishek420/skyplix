<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Diagnostics\Controller;

class DiagnosticsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $trackerUrl = \Traffic\Service\UrlService::instance()->getBaseUrl($this->getServerRequest()->getUri());
        return \Component\Diagnostics\Service\DiagnosticService::instance()->getStatus($trackerUrl);
    }
}

?>