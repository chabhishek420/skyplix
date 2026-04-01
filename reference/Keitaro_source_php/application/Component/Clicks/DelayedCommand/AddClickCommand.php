<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\DelayedCommand;

class AddClickCommand implements \Component\DelayedCommands\DelayedCommandInterface
{
    const NAME = "add_click";
    public function priority()
    {
        return 1;
    }
    public function process($entries)
    {
        $pipeline = new \Component\Clicks\ClickProcessing\Pipeline();
        $pipeline->process($entries);
    }
    public static function saveClick(\Traffic\RawClick $rawClick)
    {
        $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => $rawClick->serialize(), \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
    }
}

?>