<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Validator;

class StreamEventValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["stream_id"], ["trigger_id"], ["level"], ["state"], ["message"], ["date"]]];
    public function __construct()
    {
        $this->_rules["in"] = [["level", [\Component\Streams\Model\StreamEvent::INFO, \Component\Streams\Model\StreamEvent::WARNING]], ["state", [\Component\Streams\Model\StreamEvent::READ, \Component\Streams\Model\StreamEvent::UNREAD]]];
    }
}

?>