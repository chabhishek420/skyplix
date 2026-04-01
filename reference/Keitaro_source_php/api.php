<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

define("ROOT", realpath(dirname(__FILE__)));
require ROOT . "/vendor/autoload.php";
$response = Core\Kernel\Kernel::run(Traffic\Request\ServerRequest::build(), new Traffic\Context\ClickApiContext());
Core\ServerRenderer\ServerRenderer::renderResponse($response);

?>