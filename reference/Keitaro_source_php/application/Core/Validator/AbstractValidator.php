<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Validator;

class AbstractValidator
{
    protected $_rules = [];
    public function validate($params)
    {
        $v = new \Valitron\Validator($params);
        if (!isset($this->_rules)) {
            throw new \Exception("No rules");
        }
        $v->rules($this->_rules);
        if (!$v->validate()) {
            $this->_throwErrors($v->errors());
        }
    }
    protected function _throwErrors($errors)
    {
        throw new ValidationError($errors);
    }
}

?>