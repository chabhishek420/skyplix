<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

define("ROOT", realpath(dirname(__FILE__) . "/../"));
if (empty($_SERVER["REMOTE_ADDR"]) || $_SERVER["REMOTE_ADDR"] != "127.127.127.127") {
    header("403 Forbidden");
    exit("403 Forbidden");
}
error_reporting(22517);
$result = [];
parse_str(file_get_contents("php://input"), $result);
$params = $result["params"];
$params = json_decode($params, true);
require_once ROOT . "/application/Traffic/RawClickInterface.php";
require_once ROOT . "/application/Core/Sandbox/RawClickGetter.php";
$rawClick = new RawClickGetter($params["rawclick"]);
$_SERVER = $params["server"];
$_GET = $params["get"];
$_POST = $params["post"];
$_COOKIE = $params["cookie"];
$_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
chdir(dirname($params["filepath"]));
include $params["filepath"];

?>