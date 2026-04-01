<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\ComponentManager;

class Component
{
    private $_name = NULL;
    private $_initializer = NULL;
    const BASE_NAMESPACE = "Component";
    public function __construct($name)
    {
        $this->_name = $name;
        if ($this->_isInitializerExists()) {
            $this->_initializer = $this->_loadInitializer();
        }
    }
    public function getName()
    {
        return $this->_name;
    }
    public function getBasePath()
    {
        return ROOT . "/application/Component/" . $this->_name;
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        if ($this->_initializer) {
            $this->_initializer->loadControllers($repo);
        }
    }
    public function loadFilters(\Component\StreamFilters\Repository\FilterRepository $repo)
    {
        if ($this->_initializer) {
            $this->_initializer->loadFilters($repo);
        }
    }
    public function loadMacros(\Traffic\Macros\MacroRepository $repo)
    {
        if ($this->_initializer) {
            $this->_initializer->loadMacros($repo);
        }
    }
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        if ($this->_initializer) {
            $this->_initializer->loadCronTasks($repo);
        }
    }
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        if ($this->_initializer) {
            $this->_initializer->loadConsoleCommands($repo);
        }
    }
    public function loadDelayedCommands(\Component\DelayedCommands\Repository\DelayedCommandRepository $repo)
    {
        if ($this->_initializer) {
            $this->_initializer->loadDelayedCommands($repo);
        }
    }
    public function loadApiRoutes(\Admin\AdminApi\AdminApiRoutesRepository $repo)
    {
        if ($this->_initializer) {
            $this->_initializer->loadApiRoutes($repo);
        }
    }
    public function loadPruneTask(\Component\PruneTask\Repository\PruneTaskRepository $repo)
    {
        if ($this->_initializer) {
            $this->_initializer->loadPruneTask($repo);
        }
    }
    public function getTranslations($language)
    {
        if (file_exists($this->_getTranslationFile($language))) {
            return include $this->_getTranslationFile($language);
        }
        return [];
    }
    private function _getTranslationFile($language)
    {
        return $this->getBasePath() . "/translations/" . $language . ".php";
    }
    private function _isInitializerExists()
    {
        return file_exists($this->getBasePath() . "/Initializer.php");
    }
    private function _loadInitializer()
    {
        $className = BASE_NAMESPACE . "\\" . $this->getName() . "\\Initializer";
        require_once $this->getBasePath() . "/Initializer.php";
        if (!class_exists($className)) {
            throw new Error("Class " . $className . " not found");
        }
        return new $className();
    }
}

?>