<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System;

class Initializer extends \Core\Component\BaseInitializer
{
    const SIGNATURE = "6a204bd89f3c8348afd5c77c717a097a";
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("status", new Controller\StatusController());
        $repo->register("system", new Controller\SystemController());
    }
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        $repo->registerConsoleCommand(new ConsoleCommand\ReloadCacheCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\FlushOldNamespacedCacheCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\GenerateConfigCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\RestartRoadRunnerCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\OptimizeDbCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\DeleteAllStatsCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\SetSettingCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\SetLicenseKeyCommand());
    }
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        $repo->register(new CronTask\WarmupCacheTask());
        $repo->register(new CronTask\CheckTsTask(), true);
        $repo->register(new CronTask\FlushOldCacheTask());
    }
}

?>