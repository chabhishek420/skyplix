<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Validator;

class OfferValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["name"]], "lengthMax" => [["name", 100]], "uniqueness" => [["name", \Traffic\Model\Offer::definition(), "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED)]], "in" => [["offer_type", [\Traffic\Model\StreamActionCategory::ACTION, \Traffic\Model\StreamActionCategory::LOCAL, \Traffic\Model\StreamActionCategory::EXTERNAL, \Traffic\Model\StreamActionCategory::PRELOADED]], ["payout_type", \Traffic\Model\Offer::getValidPayoutTypes()], ["state", [\Core\Entity\State::ACTIVE, \Core\Entity\State::DELETED, \Core\Entity\State::DISABLED]]]];
    }
}

?>