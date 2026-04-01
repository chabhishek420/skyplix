<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\DelayedCommands\Repository;

class DelayedCommandRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_commands = [];
    public function __construct()
    {
        \Core\ComponentManager\ComponentManager::instance()->loadDelayedCommands($this);
    }
    public function find($commandName)
    {
        if (!isset($this->_commands[$commandName])) {
            throw new \Core\Exceptions\NotFoundError("Unknown type " . $commandName);
        }
        return $this->_commands[$commandName];
    }
    public function register($eventType, \Component\DelayedCommands\DelayedCommandInterface $command)
    {
        $this->_commands[$eventType] = $command;
    }
}

?>