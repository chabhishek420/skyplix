<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros;

class ParserItem
{
    private $_name = NULL;
    private $_originalString = NULL;
    private $_rawMode = NULL;
    private $_arguments = NULL;
    public function __construct($name, $originalString, $rawMode, $arguments)
    {
        $this->_name = $name;
        $this->_originalString = $originalString;
        $this->_rawMode = $rawMode;
        $this->_arguments = $arguments;
    }
    public function name()
    {
        return $this->_name;
    }
    public function originalString()
    {
        return $this->_originalString;
    }
    public function rawMode()
    {
        return $this->_rawMode;
    }
    public function arguments()
    {
        return $this->_arguments;
    }
    public function setRawMode($mode)
    {
        $this->_rawMode = $mode;
    }
}

?>