<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

if (!defined("ROOT")) {
    define("ROOT", realpath(dirname(__FILE__) . "/../"));
}
require ROOT . "/vendor/autoload.php";
if (!defined("CRON") && php_sapi_name() !== "cli") {
    header("403 Forbidden");
    exit("403 Forbidden");
}
if (!defined("TESTS_ROOT")) {
    define("TESTS_ROOT", dirname(__FILE__));
}
if (getenv("DB_DISABLED")) {
    Core\Db\Db::instance()->disable();
}
$response = Core\Kernel\Kernel::run(Traffic\Request\ServerRequest::build(), new Admin\Context\CliContext());
if (!$response->getStatusCode() == 200) {
    exit((int) $response->getBody());
}

?>