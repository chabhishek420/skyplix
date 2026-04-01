<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        $repo->register(new CronTask\PruneOldFiles());
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("reports", new Controller\ReportsController());
        $repo->register("exportedReports", new Controller\ExportedReportsController());
        $repo->register("favouriteReports", new Controller\FavouriteReportController());
        $repo->register("labels", new Controller\LabelsController());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "POST", "route" => "/clicks/summary", "desc" => "Retrieve summary data", "onMatch" => function () {
            return ["controller" => "reports", "action" => "summary"];
        }]);
        $repo->register(["method" => "POST", "route" => "/report/build", "desc" => "Build a report", "onMatch" => function () {
            return ["controller" => "reports", "action" => "build"];
        }]);
        $repo->register(["method" => "GET", "route" => "/report/labels", "desc" => "Retrieve labels", "onMatch" => function () {
            return ["controller" => "labels", "action" => "index"];
        }]);
        $repo->register(["method" => "POST", "route" => "/report/labels", "desc" => "Update labels", "onMatch" => function () {
            return ["controller" => "labels", "action" => "update"];
        }]);
    }
}

?>