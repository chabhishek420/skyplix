<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\Controller;

class PostbackTemplatesController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        return \Component\AffiliateNetworks\Repository\NetworkTemplatesRepository::instance()->getTemplates($this->getParam("includeOther"));
    }
    public function findAction()
    {
        $name = $this->getParam("name");
        return \Component\AffiliateNetworks\Repository\NetworkTemplatesRepository::instance()->getTemplate($name);
    }
}

?>