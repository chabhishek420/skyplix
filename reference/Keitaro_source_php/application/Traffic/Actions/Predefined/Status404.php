<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class Status404 extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 12;
    const DESTINATION = "404 Not Found";
    const NOT_FOUND = "HTTP/1.1 404 Not Found";
    public function getType()
    {
        return TYPE_OTHER;
    }
    public function getField()
    {
        return NOTHING;
    }
    protected function _execute()
    {
        $this->setStatus(404);
        $this->setDestinationInfo(DESTINATION);
    }
}

?>