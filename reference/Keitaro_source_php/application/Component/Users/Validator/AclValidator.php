<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Validator;

class AclValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["user_id"], ["access_type"], ["entity_type"]], "uniqueness" => [["user_id,entity_type", \Component\Users\Model\AclRule::definition(), "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED)]]];
    }
}

?>