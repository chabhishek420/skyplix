<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\SelfUpdate\ConsoleCommand;

class SystemSelfUpdateCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Update Keitaro (don't run the command as root!)")->setName("system:self_update");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (\Traffic\Tools\Tools::isRootUser()) {
            $output->writeln("User 'root' can't run this command. You must use 'sudo -u USER php bin/cli.php.system:self_update'");
            return 1;
        }
        chdir(ROOT . "/admin");
        if (!\Component\SelfUpdate\Service\SystemUpdaterService::instance()->isUpdateAvailable()) {
            $output->writeln("Keitaro is up-to-date");
            return 1;
        }
        $updater = new \Component\SelfUpdate\KeitaroUpdater\KeitaroUpdater();
        $updater->update();
        $runner = new \Component\Migrations\MigrationRunner\MigrationRunner();
        $runner->runAll();
        $output->writeln("Successfully applied update");
        return 0;
    }
}

?>