<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\DelayedCommands\Processor;

class ProcessCommandQueue
{
    public function process(\Traffic\CommandQueue\Service\DelayedCommandService $service)
    {
        $commandAggregator = new CommandAggregator();
        $count = $service->count();
        \Traffic\Logging\Service\LoggerService::instance()->info("Ready to process " . $count . " command");
        foreach ($service->pop() as $command) {
            $commandAggregator->store($command);
        }
        \Traffic\Logging\Service\LoggerService::instance()->info("Finish processing commands");
        $commandAggregator->flushAll();
    }
}

?>