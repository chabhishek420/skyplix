<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Trends\Controller;

class TrendsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
    }
    public function definitionAction()
    {
        $definition = new \Component\Trends\TrendsDefinition();
        return $definition->getGridDefinition();
    }
}

?>