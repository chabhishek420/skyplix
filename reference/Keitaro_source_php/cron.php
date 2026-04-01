<?php

define("CRON", true);
define("ROOT", realpath(dirname(__FILE__)));
require ROOT . "/vendor/autoload.php";
if (Traffic\Tools\Tools::isRootUser()) {
    $msg = "Running cron.php by 'root' user is not recommended. You should run cron.php by another user.";
    Traffic\Logging\Service\LoggerService::instance()->warning($msg);
}
set_time_limit(0);
ignore_user_abort();
$channel = NULL;
if (getenv("CHANNEL")) {
    $channel = getenv("CHANNEL");
}
$request = Traffic\Request\ServerRequest::build(["query_params" => ["channel" => $channel]]);
$context = new Cron\Context\CronContext();
$response = Core\Kernel\Kernel::run($request, $context);
$renderer = new Core\ServerRenderer\ServerRenderer();
$renderer->render($response);

?>