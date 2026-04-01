<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

require "vendor/autoload.php";
define("ROOT", realpath(dirname(__FILE__)));
$server = new Traffic\RoadRunner\Server();
$router = new Core\Router\TrafficRouter();
$server->start($router);

?>