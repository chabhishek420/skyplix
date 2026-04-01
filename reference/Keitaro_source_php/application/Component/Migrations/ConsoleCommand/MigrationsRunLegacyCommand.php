<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\ConsoleCommand;

class MigrationsRunLegacyCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Run legacy migration")->setName("migrations:run_legacy")->addArgument("version", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "version of migration");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $legacyMigrations = \Component\Migrations\Repository\LegacyMigrationsRepository::instance()->getMigrations();
        $currentVersion = \Component\Migrations\Service\LegacyMigrationRunnerService::instance()->getCurrentVersion();
        $migrations = \Component\Migrations\Repository\MigrationsRepository::instance()->getMigrations();
        $data = \Core\Json\SerializerFactory::serialize($legacyMigrations, new \Component\Migrations\Serializer\LegacyMigrationSerializer());
        $data2 = \Core\Json\SerializerFactory::serialize($migrations, new \Component\Migrations\Serializer\MigrationSerializer());
        if ($version = $input->getArgument("version")) {
            $runner = new \Component\Migrations\Service\LegacyMigrationRunnerService();
            if (!is_numeric($version)) {
                throw new \Exception("Incorrect version value");
            }
            $runner->run($version);
            $output->writeln("Legacy - complete");
        }
        return 0;
    }
}

?>