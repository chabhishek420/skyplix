<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\SelfUpdate\ConsoleCommand;

class SystemCheckUpdateCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("List of available updates")->setName("system:check_update");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $update = \Component\SelfUpdate\Service\SystemUpdaterService::instance()->getUpdate(phpversion(), TDS_VERSION);
        $output->writeln("Current version: " . TDS_VERSION);
        if ($update) {
            $output->writeln("New version is available: " . $update["version"]);
        } else {
            $output->writeln("Latest version installed");
        }
        return 0;
    }
}

?>