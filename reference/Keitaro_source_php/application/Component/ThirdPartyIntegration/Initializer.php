<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("thirdpartyintegration", new Controller\ThirdPartyIntegrationController());
        $repo->register("tpimandatory", new Controller\TPIMandatoryController());
        $repo->register("facebookintegration", new Controller\FacebookController());
        $repo->register("appsflyerintegration", new Controller\AppsFlyerController());
    }
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        $repo->register(new CronTask\SyncCostsWithFacebook());
        $repo->register(new CronTask\SyncConversionAppsFlyer());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "GET", "route" => "/integrations/avscan", "desc" => "Get AVScan key", "onMatch" => function () {
            return ["controller" => "thirdpartyintegration", "action" => "getSettingsIntegration", "params" => ["param" => "avscan_key"]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/integrations/avscan", "desc" => "Update AVScan integration", "onMatch" => function () {
            return ["controller" => "thirdpartyintegration", "action" => "updateSettingsIntegration", "params" => ["param" => "avscan_key"]];
        }]);
        $repo->register(["method" => "GET", "route" => "/integrations/imklo", "desc" => "Get IMKLO key", "onMatch" => function () {
            return ["controller" => "thirdpartyintegration", "action" => "getSettingsIntegration", "params" => ["param" => "imklo_api_url"]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/integrations/imklo", "desc" => "Update IMKLO integration", "onMatch" => function () {
            return ["controller" => "thirdpartyintegration", "action" => "updateSettingsIntegration", "params" => ["param" => "imklo_api_url"]];
        }]);
        $repo->register(["method" => "GET", "route" => "/integrations/hide_click", "desc" => "Get HideClick key", "onMatch" => function () {
            return ["controller" => "thirdpartyintegration", "action" => "getSettingsIntegration", "params" => ["param" => "hide_click_key"]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/integrations/hide_click", "desc" => "Update HideClick integration", "onMatch" => function () {
            return ["controller" => "thirdpartyintegration", "action" => "updateSettingsIntegration", "params" => ["param" => "hide_click_key"]];
        }]);
        $repo->register(["method" => "GET", "route" => "/integrations/facebook", "desc" => "Get Facebook integrations", "onMatch" => function () {
            return ["controller" => "thirdpartyintegration", "action" => "get", "params" => ["integration" => "facebook"]];
        }]);
        $repo->register(["method" => "POST", "route" => "/integrations/facebook", "desc" => "Create Facebook integrations", "onMatch" => function () {
            return ["controller" => "thirdpartyintegration", "action" => "create", "params" => ["integration" => "facebook"]];
        }]);
        $repo->register(["method" => "GET", "route" => "/integrations/facebook/[i:id]", "desc" => "Get Facebook integrations", "onMatch" => function ($id) {
            return ["controller" => "thirdpartyintegration", "action" => "find", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "PUT", "route" => "/integrations/facebook/[i:id]", "desc" => "Update Facebook integrations", "onMatch" => function ($id) {
            return ["controller" => "thirdpartyintegration", "action" => "update", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/integrations/facebook/[i:id]", "desc" => "Delete Facebook integrations", "onMatch" => function ($id) {
            return ["controller" => "thirdpartyintegration", "action" => "delete", "params" => ["id" => $id]];
        }]);
        $repo->register(["method" => "GET", "route" => "/integrations/facebook/[i:id]/campaign", "desc" => "All campaigns link to Facebook integration", "onMatch" => function ($id) {
            return ["controller" => "tpimandatory", "action" => "all", "params" => ["integration_id" => $id]];
        }]);
        $repo->register(["method" => "POST", "route" => "/integrations/facebook/[i:id]/campaign", "desc" => "Add campaign to Facebook integration", "onMatch" => function ($id) {
            return ["controller" => "tpimandatory", "action" => "addCampaign", "params" => ["integration_id" => $id]];
        }]);
        $repo->register(["method" => "DELETE", "route" => "/integrations/facebook/[i:id]/campaign", "desc" => "Remove campaign from Facebook integration", "onMatch" => function ($id) {
            return ["controller" => "tpimandatory", "action" => "removeCampaign", "params" => ["integration_id" => $id]];
        }]);
    }
}

?>