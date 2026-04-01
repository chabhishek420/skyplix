<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Macros\Controller;

class MacrosController extends \Admin\Controller\BaseController
{
    public function macrosAction()
    {
        return \Traffic\Macros\MacroRepository::instance()->getActiveMacroNames($this->getParam("type"));
    }
}

?>