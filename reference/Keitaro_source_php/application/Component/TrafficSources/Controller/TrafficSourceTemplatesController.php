<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\TrafficSources\Controller;

class TrafficSourceTemplatesController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        return \Component\TrafficSources\Repository\TrafficSourceTemplateRepository::instance()->getTemplates();
    }
    public function findAction()
    {
        return \Component\TrafficSources\Repository\TrafficSourceTemplateRepository::instance()->get($this->getParam("name"));
    }
}

?>