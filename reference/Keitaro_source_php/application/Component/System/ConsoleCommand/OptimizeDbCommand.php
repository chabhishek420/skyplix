<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConsoleCommand;

class OptimizeDbCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Optimize database")->setName("system:optimize_db")->addOption("tables", "-t", \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, "List ot tables for optimize separated by \":\". By default optimize all tables in database");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $util = new \Component\System\DatabaseUtil\DatabaseUtil();
        $tablesList = $input->getOption("tables");
        if ($tablesList != "") {
            $tables = explode(":", trim($tablesList));
        } else {
            $tables = $util->getTables();
        }
        $total = 0;
        $ok = 0;
        $fail = 0;
        foreach ($tables as $tableName) {
            $total++;
            $output->writeln("Optimize " . $tableName . ". Start");
            if ($util->optimizeTable($tableName)) {
                $output->writeln("OK");
                $ok++;
            } else {
                $output->writeln("Failed");
                $fail++;
            }
        }
        $output->writeln("Total tables: " . $total . ". Success: " . $ok . ". Failed: " . $fail . ".");
        return 0;
    }
}

?>