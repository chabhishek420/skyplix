<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("campaigns", new Controller\CampaignsController());
    }
    public function loadDelayedCommands(\Component\DelayedCommands\Repository\DelayedCommandRepository $repo)
    {
        $repo->register(DelayedCommand\UpdateCostsCommand::NAME, new DelayedCommand\UpdateCostsCommand());
        $repo->register(DelayedCommand\UpdateCostsBulkCommand::NAME, new DelayedCommand\UpdateCostsBulkCommand());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/campaigns", "desc" => "Retrieve all campaigns", "onMatch" => function () {
            return ["controller" => "campaigns", "action" => "index"];
        }]);
        $repo->register(["method" => "GET", "route" => "/campaigns/[i:id]", "desc" => "Retrieve the specific campaign", "onMatch" => function ($id) {
            return ["controller" => "campaigns", "action" => "show", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/campaigns", "desc" => "Create a campaign", "onMatch" => function () {
            return ["controller" => "campaigns", "action" => "create"];
        }]);
        $repo->register(["method" => "PUT", "route" => "/campaigns/[i:id]", "desc" => "Update a campaign", "onMatch" => function ($id) {
            return ["controller" => "campaigns", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/campaigns/[i:id]", "desc" => "Move a campaign to archive", "onMatch" => function ($id) {
            return ["controller" => "campaigns", "action" => "archive", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "GET", "route" => "/campaigns/deleted", "desc" => "Retrieve all deleted campaigns", "onMatch" => function () {
            return ["controller" => "campaigns", "action" => "deleted"];
        }]);
        $repo->register(["method" => "POST", "route" => "/campaigns/[i:id]/restore", "desc" => "Restore an archived campaign", "onMatch" => function ($id) {
            return ["controller" => "campaigns", "action" => "restore", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/campaigns/[i:id]/disable", "desc" => "Disable a specific campaign", "onMatch" => function ($id) {
            return ["controller" => "campaigns", "action" => "disable", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/campaigns/[i:id]/enable", "desc" => "Enable a specific campaign", "onMatch" => function ($id) {
            return ["controller" => "campaigns", "action" => "enable", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/campaigns/[i:id]/clone", "desc" => "Clone a campaign", "onMatch" => function ($id) {
            return ["controller" => "campaigns", "action" => "clone", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/campaigns/[i:id]/update_costs", "desc" => "Update a campaign costs", "onMatch" => function ($id) {
            return ["controller" => "campaigns", "action" => "updateCosts", "params" => ["id" => $id]];
        }]);
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneCampaigns());
    }
}

?>