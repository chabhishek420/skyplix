<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Validator;

class StreamLandingAssociationValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["stream_id"], ["landing_id"], ["state"]], "uniqueness" => [["stream_id, landing_id"]]];
}

?>