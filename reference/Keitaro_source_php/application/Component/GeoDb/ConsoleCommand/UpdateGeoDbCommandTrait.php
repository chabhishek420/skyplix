<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\ConsoleCommand;

interface UpdateGeoDbCommandTrait
{
    protected function updateDb($id, \Symfony\Component\Console\Output\OutputInterface $output);
}

?>