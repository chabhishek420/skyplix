<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cleaner\DelayedCommand;

class DeleteStatsCommand implements \Component\DelayedCommands\DelayedCommandInterface
{
    const NAME = "delete_stats";
    public function priority()
    {
        return 10;
    }
    public static function schedule($payload)
    {
        $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => $payload, \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
    }
    public function process($entries)
    {
        foreach ($entries as $payload) {
            \Component\Cleaner\Service\CleanerService::instance()->deleteStats($payload);
        }
    }
}

?>