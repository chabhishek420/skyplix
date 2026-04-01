<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark\ConsoleCommand;

class DbSetupCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Create new DB and import schema.sql and tables.sql")->setName("db:setup")->addOption("config", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Config file", "application/config/config.testing.ini.php")->addOption("schema-only", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, "Create only tables", false);
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        include_once ADODB_DIR . "/adodb-exceptions.inc.php";
        $configFile = $input->getOption("config");
        $schemaOnly = $input->getOption("schema-only");
        if (!file_exists($configFile)) {
            throw new \Exception("No file " . $configFile);
        }
        $text = file_get_contents($configFile);
        $config = parse_ini_string($text, true);
        if (!file_exists(ROOT . "/../.env")) {
            throw new \Exception("no file " . realpath(ROOT . "/../.env"));
        }
        $env = \Jsefton\DotEnv\Parser::envToArray(ROOT . "/../.env");
        $rootName = "root";
        $rootPassword = $env["MARIADB_ROOT_PASSWORD"];
        if (empty($rootPassword)) {
            throw new \Exception("empty MARIADB_ROOT_PASSWORD");
        }
        $server = $config["db"]["server"];
        $port = $config["db"]["port"];
        $db = $config["db"]["name"];
        $user = $config["db"]["user"];
        $password = $config["db"]["password"];
        $connection = NewADOConnection("pdo");
        $connection->connect("mysql:host=" . $server . ";port=" . $port, $rootName, $rootPassword);
        $connection->setFetchMode(ADODB_FETCH_ASSOC);
        $connection->execute("SET character_set_client=utf8");
        $connection->execute("SET character_set_connection=utf8");
        $connection->execute("SET character_set_results=utf8");
        $connection->execute("SET collation_connection=utf8_unicode_ci");
        $connection->execute("SET SQL_BIG_SELECTS=1");
        $connection->execute("SET sql_mode=''");
        $connection->execute("CREATE DATABASE " . $db);
        $connection->execute("GRANT ALL PRIVILEGES ON " . $db . ".* TO '" . $user . "'@'%'");
        $connection->execute("USE " . $db);
        $this->_import($connection, $output, $schemaOnly);
        $output->writeLn("Done!");
        return 0;
    }
    private function _import(\ADOConnection $connection, \Symfony\Component\Console\Output\OutputInterface $output, $schemaOnly)
    {
        $sqls = explode(";", file_get_contents(ROOT . "/application/data/schema.sql"));
        if ($schemaOnly !== NULL) {
            $sqls = array_merge($sqls, explode(";", file_get_contents(ROOT . "/application/data/data.sql")));
        }
        foreach ($sqls as $sql) {
            if (trim($sql)) {
                $output->writeln($sql);
                $connection->execute($sql);
            }
        }
    }
}

?>