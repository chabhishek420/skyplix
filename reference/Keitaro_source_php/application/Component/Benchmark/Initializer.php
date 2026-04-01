<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        if (\Core\Application\Application::instance()->isDevelopment()) {
            $repo->registerConsoleCommand(new ConsoleCommand\BenchmarkCommandProcessorCommand());
            $repo->registerConsoleCommand(new ConsoleCommand\BenchmarkTrafficCommand());
            $repo->registerConsoleCommand(new ConsoleCommand\DbSetupCommand());
        }
        $repo->registerConsoleCommand(new ConsoleCommand\SeedClicksCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\SeedConversionsCommand());
    }
}

?>