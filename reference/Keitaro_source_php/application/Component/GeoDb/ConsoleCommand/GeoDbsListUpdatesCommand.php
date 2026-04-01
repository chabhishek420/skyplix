<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\ConsoleCommand;

class GeoDbsListUpdatesCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("List geodb updates")->setName("geodbs:list_updates");
    }
    protected function dumpDatabases($databases, \Symfony\Component\Console\Helper\Table $table)
    {
        foreach ($databases as $database) {
            if ($database->isUpdateAvailable()) {
                $tag = "comment";
                $status = "Yes";
            } else {
                $tag = "info";
                $status = "up-to-date";
            }
            $table->addRow([$database->definition()->id(), $database->definition()->name(), $database->definition()->type(), "<" . $tag . ">" . $status . "</" . $tag . ">"]);
        }
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table->setHeaders(["DB ID", "Name", "Type", "Update Available"]);
        $dbs = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->all();
        $output->writeln("<question>DataBases</question>");
        $this->dumpDatabases($dbs, $table);
        $table->render();
        return 0;
    }
}

?>