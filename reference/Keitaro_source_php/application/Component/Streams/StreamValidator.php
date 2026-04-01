<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams;

class StreamValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["campaign_id"], ["action_type"], ["schema"]], "lengthMax" => [["name", 100], ["action_payload", 16777215]]];
    public function __construct()
    {
        $this->_rules["in"] = [["state", [\Core\Entity\State::ACTIVE, \Core\Entity\State::DISABLED, \Core\Entity\State::DELETED]], ["type", Repository\StreamTypeRepository::instance()->getTypes()], ["action_type", \Traffic\Actions\Repository\StreamActionRepository::instance()->getNames()], ["schema", Repository\StreamSchemaRepository::instance()->getSchemas()]];
    }
}

?>