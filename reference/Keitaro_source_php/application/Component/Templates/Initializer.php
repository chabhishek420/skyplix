<?php

namespace Component\Templates;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        $repo->registerConsoleCommand(new ConsoleCommand\DownloadTemplatesCommand());
    }
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        $repo->register(new CronTask\UpdateTemplatesTask());
    }
}

?>