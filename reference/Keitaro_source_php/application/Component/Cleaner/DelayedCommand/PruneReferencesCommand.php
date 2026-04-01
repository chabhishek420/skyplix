<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cleaner\DelayedCommand;

class PruneReferencesCommand implements \Component\DelayedCommands\DelayedCommandInterface
{
    const NAME = "prune_references";
    public function priority()
    {
        return 5;
    }
    public function process($entries)
    {
        $tasks = \Component\PruneTask\Repository\PruneTaskRepository::instance()->getPruneTasks(\Component\PruneTask\Repository\PruneTaskRepository::REFERENCE_TYPE);
        foreach ($tasks as $task) {
            $task->prune();
        }
    }
    public static function schedule()
    {
        $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => [], \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
    }
}

?>