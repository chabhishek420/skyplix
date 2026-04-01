<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Validator;

class ValidationError extends \Exception
{
    protected $_errors = NULL;
    public function __construct($errors)
    {
        $this->_errors = $errors;
        parent::__construct("Validation Error: " . json_encode($errors));
    }
    public function getErrors()
    {
        return $this->_errors;
    }
}

?>