<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Validator;

class UserCampaignPermissionValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["user_id"], ["campaign_id"]], "uniqueness" => [["user_id,campaign_id", \Component\Users\Model\UserCampaignPermission::definition()]]];
    }
}

?>