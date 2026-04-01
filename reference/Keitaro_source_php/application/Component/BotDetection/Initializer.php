<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("botlist", new Controller\BotlistController());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/botlist", "desc" => "Retrieve the additional bot list", "onMatch" => function () {
            return ["controller" => "botlist", "action" => "getBotList"];
        }]);
        $repo->register(["method" => "PUT", "route" => "/botlist", "desc" => "Update the additional bot list", "onMatch" => function () {
            return ["controller" => "botlist", "action" => "saveBotList"];
        }]);
        $repo->register(["method" => "POST", "route" => "/botlist/add", "desc" => "Add IPs to the additional bot list", "onMatch" => function () {
            return ["controller" => "botlist", "action" => "addBotList"];
        }]);
        $repo->register(["method" => "POST", "route" => "/botlist/exclude", "desc" => "Exclude IPs from the additional bot list", "onMatch" => function () {
            return ["controller" => "botlist", "action" => "excludeBotList"];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/botlist", "desc" => "Clear the bot list", "onMatch" => function () {
            return ["controller" => "botlist", "action" => "cleanBotList"];
        }]);
    }
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        $repo->registerConsoleCommand(new ConsoleCommand\ImportCommand());
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneUserBotDBCA());
    }
}

?>