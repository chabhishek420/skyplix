<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Grid;

class ClickLogDefinition extends ClicksDefinition
{
    protected $_rangeTimeField = "datetime";
    protected $_url = "?object=clicks.log";
    protected $_defaultPageSize = 100;
    protected $_rangeIntervals = NULL;
}

?>