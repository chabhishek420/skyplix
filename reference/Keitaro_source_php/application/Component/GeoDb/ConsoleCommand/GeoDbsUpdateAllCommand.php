<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\ConsoleCommand;

class GeoDbsUpdateAllCommand extends \Symfony\Component\Console\Command\Command
{
    use UpdateGeoDbCommandTrait;
    protected function configure()
    {
        $this->setDescription("Update all Geo DBs")->setName("geodbs:update_all");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $dbs = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->all();
        foreach ($dbs as $db) {
            $this->updateDb($db->definition()->id(), $output);
        }
        return 0;
    }
}

?>