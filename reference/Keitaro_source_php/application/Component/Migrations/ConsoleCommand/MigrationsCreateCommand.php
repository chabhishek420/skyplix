<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\ConsoleCommand;

class MigrationsCreateCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Create new migration")->setName("migrations:create")->addArgument("name", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "Name of migration, CamelCase (NameOfMigration)")->addOption("ru-desc", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Russian description of migration(Описание миграции)\"", "")->addOption("en-desc", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "English description of migration(Migration description)", "")->addOption("dir", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Where to save migration file", ROOT . "/application/migrations2");
    }
    protected function camelCase2Underscore($camelCase)
    {
        $arr = preg_split("/(?=[A-Z])/", $camelCase);
        unset($arr[0]);
        return strtolower(implode("_", $arr));
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (!\Core\Application\Application::instance()->isDevelopment()) {
            $output->writeln("<error>Function not avaliable</error>");
        }
        $name = ucfirst($input->getArgument("name"));
        $enDesc = $input->getOption("en-desc");
        $ruDesc = $input->getOption("ru-desc");
        $date = new \DateTime();
        $migrationName = $date->format("YmdHis") . "_" . $this->camelCase2Underscore($name);
        $className = "Migration_" . $date->format("YmdHis") . "_" . $name;
        $filename = $input->getOption("dir") . "/" . $migrationName . ".php";
        if (!is_file($filename)) {
            $code = "<?php\nclass " . $className . " extends Migration \n{\n    const DESCRIPTION_RU = '" . $ruDesc . "';\n\n    const DESCRIPTION_EN = '" . $enDesc . "';\n\n    public static function up()\n    {\n    }\n}";
            file_put_contents($filename, $code);
            $output->writeln("<question>Success! Check " . realpath($filename) . "</question>");
            return 0;
        }
        $output->writeln("Filename " . $filename . " already exists");
        return 1;
    }
}

?>