<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Validator;

class UserValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["login"], ["type"], ["password_hash"]], "lengthMax" => [["login", 50]], "in" => [["type", [\Component\Users\Model\User::TYPE_USER, \Component\Users\Model\User::TYPE_ADMIN]]], "uniqueness" => [["login", \Component\Users\Model\User::definition()]]];
    }
}

?>