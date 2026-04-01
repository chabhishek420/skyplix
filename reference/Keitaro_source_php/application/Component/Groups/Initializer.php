<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Groups;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("groups", new Controller\GroupsController());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/groups", "desc" => "Get list of groups", "onMatch" => function () {
            return ["controller" => "groups", "action" => "index"];
        }]);
        $repo->register(["method" => "POST", "route" => "/groups", "desc" => "Create group", "onMatch" => function () {
            return ["controller" => "groups", "action" => "create"];
        }]);
        $repo->register(["method" => "PUT", "route" => "/groups/[i:id]", "desc" => "Update group", "onMatch" => function ($id) {
            return ["controller" => "groups", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/groups/[i:id]/delete", "desc" => "Delete group", "onMatch" => function ($id) {
            return ["controller" => "groups", "action" => "delete", "params" => ["id" => $id]];
        }]);
    }
}

?>