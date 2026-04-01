<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class Offset
{
    private $_value = NULL;
    public function __construct($offset)
    {
        $this->_value = $offset;
    }
    public function setValue($value)
    {
        $this->_value = $value;
    }
    public function getValue()
    {
        return $this->_value;
    }
    public function getSql()
    {
        if (!empty($this->_value)) {
            return "OFFSET " . $this->_value;
        }
        return NULL;
    }
}

?>