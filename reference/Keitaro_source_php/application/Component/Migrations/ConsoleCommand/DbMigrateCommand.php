<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\ConsoleCommand;

class DbMigrateCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Run all migrations (don't run the command as root!)")->setName("db:migrate")->addOption("name", "", \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "name of migration", NULL);
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (\Traffic\Tools\Tools::isRootUser() && !\Core\Application\Application::instance()->isTesting()) {
            $output->writeln("User 'root' can't run this command. You must use 'sudo -u USER php bin/cli.php db:migrate'");
            return 1;
        }
        $runner = new \Component\Migrations\MigrationRunner\MigrationRunner();
        if ($name = $input->getOption("name")) {
            $runner->run($name);
            $output->writeln("Migration - complete");
        } else {
            $runner->runAll();
            $output->writeln("Complete");
        }
        \Traffic\Service\SettingsService::instance()->refreshCache();
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
        return 0;
    }
}

?>