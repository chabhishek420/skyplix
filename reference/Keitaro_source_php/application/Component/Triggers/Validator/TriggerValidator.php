<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers\Validator;

class TriggerValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["stream_id"], ["target"], ["condition"], ["action"], ["interval"]]];
    public function __construct()
    {
        $this->_rules["in"] = [["condition", \Component\Triggers\Repository\TriggersRepository::getValidConditions()], ["action", \Component\Triggers\Repository\TriggersRepository::getValidActions()], ["target", \Component\Triggers\Repository\TriggersRepository::getValidTargets()]];
    }
}

?>