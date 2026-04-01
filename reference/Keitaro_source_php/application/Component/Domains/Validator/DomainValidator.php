<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Validator;

class DomainValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["name"], ["created_at"], ["updated_at"]], "lengthMax" => [["name", 255]], "uniqueness" => [["name", \Traffic\Model\Domain::definition(), "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED)]]];
    }
}

?>