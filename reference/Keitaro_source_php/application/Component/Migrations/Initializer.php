<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("migrations", new Controller\MigrationsController());
        $repo->register("legacyMigrations", new Controller\LegacyMigrationsController());
    }
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        $repo->registerConsoleCommand(new ConsoleCommand\MigrationsListCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\DbMigrateCommand());
        $repo->registerConsoleCommand(new ConsoleCommand\MigrationsRunLegacyCommand());
        if (\Core\Application\Application::instance()->isDevelopment()) {
            $repo->registerConsoleCommand(new ConsoleCommand\MigrationsCreateCommand());
            $repo->registerConsoleCommand(new ConsoleCommand\DbSchemaDumpCommand());
        }
    }
}

?>