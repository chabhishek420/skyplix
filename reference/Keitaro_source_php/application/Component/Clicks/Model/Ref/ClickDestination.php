<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Model\Ref;

class ClickDestination extends AbstractRef
{
    protected static $_tableName = "click_destinations";
    public static function getConditions(\Traffic\Model\Click $visit, $value, $options = NULL)
    {
        return ["destination" => $value];
    }
}

?>