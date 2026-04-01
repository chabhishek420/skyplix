<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\DelayedCommands\Repository;

class DelayedCommandsStorageRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_storages = NULL;
    const REDIS = "redis";
    const FILE = "file";
    const DB = "db";
    public function __construct()
    {
        $this->_storages = [FILE => new \Traffic\CommandQueue\QueueStorage\FileStorage(), REDIS => new \Traffic\CommandQueue\QueueStorage\RedisStorage()];
    }
    public function exists($name)
    {
        return in_array($name, [FILE, REDIS]);
    }
    public function getAvailableOnly()
    {
        $items = [];
        foreach ($this->getStorages() as $name => $storage) {
            if ($storage->isAvailable()) {
                $items[] = ["name" => \Core\Locale\LocaleService::t("settings.draft_data_storages." . $name), "value" => $name];
            }
        }
        return $items;
    }
    public function getStorages()
    {
        return $this->_storages;
    }
    public function getStorage($name)
    {
        if (empty($this->_storages[$name])) {
            throw new \Exception("No storage name " . $name);
        }
        return $this->_storages[$name];
    }
}

?>