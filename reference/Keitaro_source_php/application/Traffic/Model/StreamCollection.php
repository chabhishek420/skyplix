<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class StreamCollection
{
    private $_streams = NULL;
    public function __construct($streams)
    {
        $this->_streams = $streams;
    }
    public function byType($type)
    {
        if (!isset($this->_streams[$type])) {
            return [];
        }
        return $this->_streams[$type];
    }
}

?>