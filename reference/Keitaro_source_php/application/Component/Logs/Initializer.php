<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Logs;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        $repo->register(new CronTask\LogCleaner());
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("Logs", new Controller\LogsController());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/logs/[a:log]", "desc" => "Get log entries", "onMatch" => function ($log) {
            return ["controller" => "logs", "action" => $log];
        }]);
        $repo->register(["method" => "GET", "route" => "/logs/types", "desc" => "Get log types", "onMatch" => function () {
            return ["controller" => "logs", "action" => "types"];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/logs/[a:log]", "desc" => "Clean a log file", "onMatch" => function ($log) {
            return ["controller" => "logs", "action" => "delete" . ucfirst($log)];
        }]);
    }
}

?>