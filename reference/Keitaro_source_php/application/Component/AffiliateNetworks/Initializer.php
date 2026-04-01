<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AffiliateNetworks;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("affiliateNetworks", new Controller\AffiliateNetworksController());
        $repo->register("affiliateNetworkTemplates", new Controller\AffiliateNetworkTemplatesController());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/affiliate_networks", "desc" => "Get list of affiliate networks", "onMatch" => function () {
            return ["controller" => "affiliateNetworks", "action" => "index"];
        }]);
        $repo->register(["method" => "GET", "route" => "/affiliate_networks/[i:id]", "desc" => "Get affiliate network details", "onMatch" => function ($id) {
            return ["controller" => "affiliateNetworks", "action" => "show", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/affiliate_networks/[i:id]/clone", "desc" => "Clone affiliate network", "onMatch" => function ($id) {
            return ["controller" => "affiliateNetworks", "action" => "clone", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/affiliate_networks", "desc" => "Create affiliate Network", "onMatch" => function () {
            return ["controller" => "affiliateNetworks", "action" => "create"];
        }]);
        $repo->register(["method" => "PUT", "route" => "/affiliate_networks/[i:id]", "desc" => "Update affiliate network", "onMatch" => function ($id) {
            return ["controller" => "affiliateNetworks", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/affiliate_networks/[i:id]", "desc" => "Archive affiliate network", "onMatch" => function ($id) {
            return ["controller" => "affiliateNetworks", "action" => "archive", "params" => ["id" => $id]];
        }]);
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        $repo->register(new PruneTask\PruneAffiliateNetworks());
    }
}

?>