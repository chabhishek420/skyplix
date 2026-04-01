<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadMacros(\Traffic\Macros\MacroRepository $repo)
    {
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("domains", new Controller\DomainsController());
    }
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        $repo->registerConsoleCommand(new ConsoleCommand\EnableSSLCommand());
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneDomains());
    }
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        $repo->register(new CronTask\CheckDomains());
        $repo->register(new CronTask\EnableSSLTask());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/domains", "desc" => "Retrieve all domains", "onMatch" => function () {
            return ["controller" => "domains", "action" => "index"];
        }]);
        $repo->register(["method" => "GET", "route" => "/domains/[i:id]", "desc" => "Retrieve the specific domain", "onMatch" => function ($id) {
            return ["controller" => "domains", "action" => "show", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/domains/[i:id]/check", "desc" => "Update status of the specific domain", "onMatch" => function ($id) {
            return ["controller" => "domains", "action" => "updateStatus", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/domains", "desc" => "Create a domain", "onMatch" => function () {
            return ["controller" => "domains", "action" => "create"];
        }]);
        $repo->register(["method" => "PUT", "route" => "/domains/[i:id]", "desc" => "Update a domain", "onMatch" => function ($id) {
            return ["controller" => "domains", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/domains/[i:id]", "desc" => "Move domain to archive", "onMatch" => function ($id) {
            return ["controller" => "domains", "action" => "archive", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "GET", "route" => "/domains/deleted", "desc" => "Retrieve all deleted domains", "onMatch" => function () {
            return ["controller" => "domains", "action" => "deleted"];
        }]);
        $repo->register(["method" => "POST", "route" => "/domains/[i:id]/restore", "desc" => "Restore an archived domain", "onMatch" => function ($id) {
            return ["controller" => "domains", "action" => "restore", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/domains/[i:id]/clone", "desc" => "Clone a domain", "onMatch" => function ($id) {
            return ["controller" => "domains", "action" => "clone", "params" => ["id" => $id]];
        }]);
    }
}

?>