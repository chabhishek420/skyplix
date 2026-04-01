<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Validator;

class StreamOfferAssociationValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["stream_id"], ["offer_id"], ["state"]], "uniqueness" => [["stream_id, offer_id", \Traffic\Model\StreamOfferAssociation::definition()]]];
    }
}

?>