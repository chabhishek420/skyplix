<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Json;

define("JSON_SLICE", 1);
define("JSON_IN_STR", 2);
define("JSON_IN_ARR", 3);
define("JSON_IN_OBJ", 4);
define("JSON_IN_CMT", 5);
define("JSON_LOOSE_TYPE", 16);
define("JSON_SUPPRESS_ERRORS", 32);
class Error
{
    public function __construct($message = "unknown error", $code = NULL, $mode = NULL, $options = NULL, $userinfo = NULL)
    {
        echo $message;
    }
}

?>