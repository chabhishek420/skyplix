<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\ConsoleCommand;

class DbSchemaDumpCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("dump db schema")->setName("db:schema_dump");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        \Component\Migrations\Service\SqlDumpService::instance()->dump();
        $output->write("Done");
        return 0;
    }
}

?>