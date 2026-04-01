<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("users", new Controller\UsersController());
        $repo->register("apiKeys", new Controller\ApiKeysController());
        $repo->register("auth", new Controller\AuthController());
        $repo->register("profile", new Controller\ProfileController());
        $repo->register("resources", new Controller\ResourceController());
        $repo->register("userPreferences", new Controller\UserPreferencesController());
    }
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        $repo->registerConsoleCommand(new ConsoleCommand\CreateAdminCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\ChangeAdminPasswordCommand());
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneUserPasswordHash());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/users", "desc" => "Get list of users", "onMatch" => function () {
            return ["controller" => "users", "action" => "index"];
        }]);
        $repo->register(["method" => "POST", "route" => "/users", "desc" => "Create user", "onMatch" => function () {
            return ["controller" => "users", "action" => "create"];
        }]);
        $repo->register(["method" => "GET", "route" => "/users/[i:id]", "desc" => "Get user", "onMatch" => function ($id) {
            return ["controller" => "users", "action" => "show", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/users/[i:id]", "desc" => "Update user", "onMatch" => function ($id) {
            return ["controller" => "users", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/users/[i:id]", "desc" => "Delete user", "onMatch" => function ($id) {
            return ["controller" => "users", "action" => "delete", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/users/[i:id]/access", "desc" => "Update user access data", "onMatch" => function ($id) {
            return ["controller" => "users", "action" => "setAccessData", "params" => ["id" => $id]];
        }]);
    }
}

?>