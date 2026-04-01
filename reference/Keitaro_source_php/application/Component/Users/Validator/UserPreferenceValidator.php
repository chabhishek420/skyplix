<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Validator;

class UserPreferenceValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["user_id"], ["pref_name"], ["pref_value"]]];
}

?>