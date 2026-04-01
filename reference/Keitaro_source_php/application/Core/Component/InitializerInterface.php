<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Component;

final class InitializerInterface
{
    public abstract function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo);
    public abstract function loadControllers(\Admin\Controller\ControllerRepository $repo);
    public abstract function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo);
    public abstract function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo);
    public abstract function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo);
}

?>