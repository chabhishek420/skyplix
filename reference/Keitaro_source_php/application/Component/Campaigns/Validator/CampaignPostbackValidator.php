<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Validator;

class CampaignPostbackValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["url"], ["campaign_id"], ["method"]]];
}

?>