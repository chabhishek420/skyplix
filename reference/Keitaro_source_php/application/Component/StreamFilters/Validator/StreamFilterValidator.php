<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Validator;

class StreamFilterValidator extends \Core\Validator\AbstractValidator
{
    protected $_rules = ["required" => [["mode"], ["stream_id"]]];
    const MAX_PAYLOAD_LENGTH = 65534;
    public function __construct()
    {
        $this->_rules["in"][] = ["name", \Component\StreamFilters\Repository\FilterRepository::instance()->getFilterNames()];
        $this->_rules["lengthmax"][] = ["payload", MAX_PAYLOAD_LENGTH];
    }
    public function validate($params)
    {
        if (isset($params["payload"]) && is_array($params["payload"])) {
            $params["payload"] = json_encode($params["payload"]);
        }
        self::validate($params);
    }
}

?>