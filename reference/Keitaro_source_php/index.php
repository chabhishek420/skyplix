<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

define("ROOT", realpath(dirname(__FILE__)));
require ROOT . "/vendor/autoload.php";
$request = Traffic\Request\ServerRequest::build();
$router = new Core\Router\TrafficRouter();
$routerResult = $router->match($request);
$response = Core\Kernel\Kernel::run($routerResult->serverRequest(), $routerResult->context());
Core\ServerRenderer\ServerRenderer::renderResponse($response);

?>