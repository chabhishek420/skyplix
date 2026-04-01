<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cleaner;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadDelayedCommands(\Component\DelayedCommands\Repository\DelayedCommandRepository $repo)
    {
        $repo->register(DelayedCommand\PruneReferencesCommand::NAME, new DelayedCommand\PruneReferencesCommand());
        $repo->register(DelayedCommand\DeleteStatsCommand::NAME, new DelayedCommand\DeleteStatsCommand());
    }
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        $repo->register(new CronTask\PruneData());
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("cleaner", new Controller\CleanerController());
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        $repo->register(["method" => "POST", "route" => "/clicks/clean", "desc" => "Clean stats (params timezone, start_date, end_date, campaign_id)", "onMatch" => function () {
            return ["controller" => "cleaner", "action" => "clean"];
        }]);
    }
}

?>