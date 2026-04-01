<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\Validator;

class ConversionValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["stream_id"], ["campaign_id"], ["sub_id"], ["datetime"]]];
}

?>