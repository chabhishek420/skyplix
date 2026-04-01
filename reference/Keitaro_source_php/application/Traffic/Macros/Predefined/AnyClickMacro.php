<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class AnyClickMacro extends \Traffic\Macros\AbstractClickMacro
{
    private $_paramName = NULL;
    public function __construct($paramName)
    {
        $this->_paramName = $paramName;
    }
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick)
    {
        $rawClick->get($this->_paramName) ? exit : "";
    }
}

?>