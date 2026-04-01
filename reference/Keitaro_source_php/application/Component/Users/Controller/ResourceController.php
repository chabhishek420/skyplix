<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Controller;

class ResourceController extends \Admin\Controller\BaseController
{
    public function mandatoryAction()
    {
        $resources = \Component\Users\Repository\AclResourceRepository::instance()->getMandatory();
        return $resources;
    }
    public function complementaryAsOptionsAction()
    {
        $resources = \Component\Users\Repository\AclResourceRepository::instance()->getComplementaryAsOptions();
        return $resources;
    }
}

?>