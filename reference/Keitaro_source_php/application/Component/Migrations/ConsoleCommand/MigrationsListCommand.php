<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\ConsoleCommand;

class MigrationsListCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("List all migrations")->setName("migrations:list");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        \Core\Locale\LocaleService::instance()->setLanguage("en");
        $legacyMigrations = \Component\Migrations\Repository\LegacyMigrationsRepository::instance()->getMigrations();
        $currentVersion = \Component\Migrations\Service\LegacyMigrationRunnerService::instance()->getCurrentVersion();
        $migrations = \Component\Migrations\Repository\MigrationsRepository::instance()->getMigrations();
        $data = \Core\Json\SerializerFactory::serialize($legacyMigrations, new \Component\Migrations\Serializer\LegacyMigrationSerializer());
        $data2 = \Core\Json\SerializerFactory::serialize($migrations, new \Component\Migrations\Serializer\MigrationSerializer());
        foreach ($data2 as $migration) {
            $migrationInstance = \Component\Migrations\Repository\MigrationsRepository::instance()->getMigration($migration["name"]);
            if (\Component\Migrations\Service\MigrationRunnerService::instance()->isMigrationApplied($migrationInstance)) {
                $status = "applied";
                $tag = "info";
            } else {
                $status = "pending";
                $tag = "comment";
            }
            $output->writeln("<" . $tag . ">" . $migration["name"] . "|" . $migration["description"] . "|" . $status . "</" . $tag . ">");
        }
        foreach ($data as $migration) {
            if ($migration["version"] <= $currentVersion) {
                $status = "applied";
                $tag = "info";
            } else {
                $status = "pending";
                $tag = "comment";
            }
            $output->writeln("<" . $tag . ">" . $migration["version"] . "|" . $migration["description"] . "|" . $status . "</" . $tag . ">");
        }
        return 0;
    }
}

?>