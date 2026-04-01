<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\ComponentManager;

class ComponentManager extends \Traffic\Service\AbstractService
{
    private $_components = [];
    private $_exclude = ["Backup", "GeoDbs", "DeviceDetection", "Api", "Integration", "Report"];
    public function __construct()
    {
        $this->init();
    }
    public function exclude($names)
    {
        $this->_exclude = $names;
    }
    public function getComponents()
    {
        return $this->_components;
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        foreach ($this->_components as $component) {
            $component->loadControllers($repo);
        }
    }
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        foreach ($this->_components as $component) {
            $component->loadCronTasks($repo);
        }
    }
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        foreach ($this->_components as $component) {
            $component->loadConsoleCommands($repo);
        }
    }
    public function loadDelayedCommands(\Component\DelayedCommands\Repository\DelayedCommandRepository $repo)
    {
        foreach ($this->_components as $component) {
            $component->loadDelayedCommands($repo);
        }
    }
    public function loadTranslations($language)
    {
        $translations = [];
        foreach ($this->_components as $component) {
            $translations = array_merge($translations, $component->getTranslations($language));
        }
        return $translations;
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        foreach ($this->_components as $component) {
            $component->loadPruneTask($repo);
        }
    }
    public function init()
    {
        $directory = ROOT . "/application/Component";
        $iterator = new \DirectoryIterator($directory);
        $this->_components = [];
        foreach ($iterator as $directory) {
            if (!$directory->isDot() && !in_array($directory->getFilename(), $this->_exclude)) {
                $this->_components[] = new Component($directory->getFilename());
            }
        }
    }
}

?>