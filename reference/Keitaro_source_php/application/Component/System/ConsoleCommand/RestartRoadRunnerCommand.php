<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConsoleCommand;

class RestartRoadRunnerCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Restart roadrunner server")->setName("system:restart_roadrunner");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (\Traffic\RoadRunner\Server::restart()) {
            $output->writeln("Roadrunner workers are being restarted");
            return 0;
        }
        $output->writeln("Roadrunner is not running or unavailable");
        return 1;
    }
}

?>