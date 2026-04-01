<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Validator;

class CampaignValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["name"], ["alias"]], "lengthMax" => [["name", 120]], "uniqueness" => [["alias", \Traffic\Model\Campaign::definition(), "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED)]], "slug" => "alias", "in" => [["state", [\Core\Entity\State::ACTIVE, \Core\Entity\State::DISABLED, \Core\Entity\State::DELETED]]]];
    }
}

?>