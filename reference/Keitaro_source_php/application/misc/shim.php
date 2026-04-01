<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

require __DIR__ . "/../../vendor/ircmaxell/password-compat/lib/password.php";
require __DIR__ . "/bcmath_shim.php";
if (!function_exists("parse_ini_string")) {
    function parse_ini_string($str, $ProcessSections = true)
    {
        $lines = explode("\n", $str);
        $return = [];
        $inSect = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if (!(!$line || $line[0] == "#" || $line[0] == ";")) {
                if ($line[0] == "[" && ($endIdx = strpos($line, "]"))) {
                    $inSect = substr($line, 1, $endIdx - 1);
                } else {
                    if (strpos($line, "=")) {
                        $tmp = explode("=", $line, 2);
                        $value = trim($tmp[1]);
                        $value = preg_replace("/^\"(.*)\"\$/", "\$1", $value);
                        if ($value == "true") {
                            $value = 1;
                        }
                        if ($value == "false") {
                            $value = false;
                        }
                        if ($ProcessSections && $inSect) {
                            $return[$inSect][trim($tmp[0])] = $value;
                        } else {
                            $return[trim($tmp[0])] = $value;
                        }
                    }
                }
            }
        }
        return $return;
    }
}
if (!function_exists("array_replace_recursive")) {
    function array_replace_recursive($array, $array1)
    {
        function recurse($array, $array1)
        {
            foreach ($array1 as $key => $value) {
                if (!isset($array[$key]) || isset($array[$key]) && !is_array($array[$key])) {
                    $array[$key] = [];
                }
                if (is_array($value)) {
                    $value = recurse($array[$key], $value);
                }
                $array[$key] = $value;
            }
            return $array;
        }
        $args = func_get_args();
        $array = $args[0];
        if (!is_array($array)) {
            return $array;
        }
        for ($i = 1; $i < count($args); $i++) {
            if (is_array($args[$i])) {
                $array = recurse($array, $args[$i]);
            }
        }
        return $array;
    }
}
if (!function_exists("array_pluck")) {
    function array_pluck($array, $key)
    {
        $result = [];
        foreach ($array as $item) {
            if (isset($item[$key])) {
                $result[] = $item[$key];
            }
        }
        return $result;
    }
}
if (!function_exists("mb_ucfirst")) {
    function mb_ucfirst($str, $enc = "utf-8")
    {
        return mb_strtoupper(mb_substr($str, 0, 1, $enc), $enc) . mb_substr($str, 1, mb_strlen($str, $enc), $enc);
    }
}
if (!function_exists("gzopen") && function_exists("gzopen64")) {
    function gzopen($filename, $mode, $use_include_path = 0)
    {
        return gzopen64($filename, $mode, $use_include_path);
    }
}
if (!function_exists("array_flatten")) {
    function array_flatten($array, $return = [])
    {
        $flattened_array = [];
        array_walk_recursive($array, function ($a) {
            $flattened_array[] = $a;
        });
        return $flattened_array;
    }
}
if (!function_exists("utf8_encode")) {
    function utf8_encode($value)
    {
        return $value;
    }
}
if (!defined("JSON_PARTIAL_OUTPUT_ON_ERROR")) {
    define("JSON_PARTIAL_OUTPUT_ON_ERROR", JSON_UNESCAPED_UNICODE);
}
if (!function_exists("filter_var")) {
    define("FILTER_VALIDATE_IP", "validate_ip");
    function filter_var($value, $type)
    {
        switch ($type) {
            case FILTER_VALIDATE_IP:
                return preg_match("/[0-9]+\\.[0-9]+\\.[0-9]+\\.[0-9]+/", $value);
                break;
            default:
                throw new Exception("Reimplamented filter_var error: invalid type " . $type);
        }
    }
}
if (!function_exists("apache_request_headers")) {
    function apache_request_headers()
    {
        $arh = [];
        $rx_http = "/\\AHTTP_/";
        foreach ($_SERVER as $key => $val) {
            if (preg_match($rx_http, $key)) {
                $arh_key = preg_replace($rx_http, "", $key);
                $rx_matches = [];
                $rx_matches = explode("_", $arh_key);
                if (0 < count($rx_matches) && 2 < strlen($arh_key)) {
                    foreach ($rx_matches as $ak_key => $ak_val) {
                        $rx_matches[$ak_key] = ucfirst($ak_val);
                    }
                    $arh_key = implode("-", $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return $arh;
    }
}

?>