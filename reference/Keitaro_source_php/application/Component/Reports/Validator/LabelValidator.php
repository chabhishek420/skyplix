<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Validator;

class LabelValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["campaign_id"], ["label_name"], ["ref_name"], ["ref_id"]]];
}

?>