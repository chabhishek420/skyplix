<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadDelayedCommands(\Component\DelayedCommands\Repository\DelayedCommandRepository $repo)
    {
        $repo->register(DelayedCommand\AddClickCommand::NAME, new DelayedCommand\AddClickCommand());
        $repo->register(\Traffic\Command\DelayedCommand\UpdateClickCommand::NAME, new \Traffic\Command\DelayedCommand\UpdateClickCommand());
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("clicks", new Controller\ClicksController());
    }
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        $repo->register(new CronTask\PruneClicks());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "POST", "route" => "/clicks/log", "desc" => "Retrieve the clicks", "onMatch" => function () {
            return ["controller" => "clicks", "action" => "log"];
        }]);
        $repo->register(["method" => "POST", "route" => "/clicks/update_costs", "desc" => "Update costs", "onMatch" => function () {
            return ["controller" => "clicks", "action" => "updateCosts"];
        }]);
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneVisitors());
        $repo->register(new PruneTask\PruneReferences());
    }
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        $repo->registerConsoleCommand(new ConsoleCommand\UpdateVisitorGeoCommand());
    }
}

?>