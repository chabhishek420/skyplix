<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

define("ROOT", realpath(dirname(__FILE__) . "/../"));
require ROOT . "/vendor/autoload.php";
$request = Traffic\Request\ServerRequest::build();
$response = Core\Kernel\Kernel::run($request, new Admin\Context\AdminContext());
Core\ServerRenderer\ServerRenderer::renderResponse($response);

?>