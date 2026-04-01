<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Console\Repository;

class ConsoleCommandRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_consoleCommands = [];
    public function __construct()
    {
        \Core\ComponentManager\ComponentManager::instance()->loadConsoleCommands($this);
    }
    public function getCommands()
    {
        return $this->_consoleCommands;
    }
    public function registerConsoleCommand($obj)
    {
        $this->_consoleCommands[] = $obj;
    }
    public function getConsoleCommands()
    {
        return $this->_consoleCommands;
    }
}

?>