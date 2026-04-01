<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("streamFilters", new Controller\StreamFiltersController());
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneHitLimits());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/stream_filters", "desc" => "Retrieve stream filters", "onMatch" => function () {
            return ["controller" => "streamFilters", "action" => "filters"];
        }]);
    }
}

?>