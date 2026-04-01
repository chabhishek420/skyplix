<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class DoNothing extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 100;
    const DO_NOTHING = "Do nothing";
    public function getType()
    {
        return TYPE_OTHER;
    }
    public function getField()
    {
        return NOTHING;
    }
    public function _execute()
    {
        $this->setDestinationInfo(DO_NOTHING);
    }
}

?>