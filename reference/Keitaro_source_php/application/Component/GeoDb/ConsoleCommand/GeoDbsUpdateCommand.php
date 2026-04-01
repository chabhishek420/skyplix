<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\ConsoleCommand;

class GeoDbsUpdateCommand extends \Symfony\Component\Console\Command\Command
{
    use UpdateGeoDbCommandTrait;
    protected function configure()
    {
        $this->setDescription("Update specific geodb")->setName("geodbs:update")->addArgument("db-id", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "Name of db to update");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $dbId = $input->getArgument("db-id");
        $this->updateDb($dbId, $output);
        return 0;
    }
}

?>