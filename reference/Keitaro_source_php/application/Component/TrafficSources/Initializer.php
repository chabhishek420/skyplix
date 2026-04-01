<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\TrafficSources;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("trafficSources", new Controller\TrafficSourcesController());
        $repo->register("trafficSourceTemplates", new Controller\TrafficSourceTemplatesController());
    }
    public function loadMacros(\Traffic\Macros\MacroRepository $repo)
    {
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/traffic_sources", "desc" => "Retrieve all sources", "onMatch" => function () {
            return ["controller" => "trafficSources", "action" => "index"];
        }]);
        $repo->register(["method" => "GET", "route" => "/traffic_sources/[i:id]", "desc" => "Retrieve a specific source", "onMatch" => function ($id) {
            return ["controller" => "trafficSources", "action" => "show", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/traffic_sources/[i:id]/clone", "desc" => "Clone source", "onMatch" => function ($id) {
            return ["controller" => "trafficSources", "action" => "clone", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/traffic_sources", "desc" => "Create a source", "onMatch" => function () {
            return ["controller" => "trafficSources", "action" => "create"];
        }]);
        $repo->register(["method" => "PUT", "route" => "/traffic_sources/[i:id]", "desc" => "Update a source", "onMatch" => function ($id) {
            return ["controller" => "trafficSources", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/traffic_sources/[i:id]", "desc" => "Move to archive a source", "onMatch" => function ($id) {
            return ["controller" => "trafficSources", "action" => "archive", "params" => ["id" => $id]];
        }]);
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneTrafficSources());
    }
}

?>