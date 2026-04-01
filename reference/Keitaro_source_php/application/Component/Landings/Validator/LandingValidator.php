<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Validator;

class LandingValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["name"], ["created_at"], ["updated_at"]], "lengthMax" => [["name", 100], ["folder", 100]], "uniqueness" => [["name", \Traffic\Model\Landing::definition(), "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED)], ["folder", \Traffic\Model\Landing::definition()]], "slug" => [["folder"]], "in" => [["landing_type", \Component\StreamActions\Repository\StreamActionCategoryRepository::instance()->getTypes()]]];
    }
}

?>