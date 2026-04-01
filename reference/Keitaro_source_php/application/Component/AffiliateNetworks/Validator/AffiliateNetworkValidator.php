<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AffiliateNetworks\Validator;

class AffiliateNetworkValidator extends \Core\Validator\AbstractValidator
{
    public function __construct()
    {
        $this->_rules = ["required" => [["name"]], "lengthMax" => [["name", 100]], "uniqueness" => [["name", \Traffic\Model\AffiliateNetwork::definition(), "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED)]]];
    }
}

?>