<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Controller;

class ControllerRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_controllers = [];
    public function loadControllers()
    {
        if (!empty($this->_controllers)) {
            return NULL;
        }
        \Core\ComponentManager\ComponentManager::instance()->loadControllers($this);
    }
    public function register($name, $obj)
    {
        $name = strtolower($name);
        if (!empty($this->_controllers[$name])) {
            throw new \Exception("Controller '" . $name . "' is already exists");
        }
        $this->_controllers[$name] = $obj;
    }
    public function getController($name)
    {
        $this->loadControllers();
        $name = strtolower($name);
        if (empty($this->_controllers[$name])) {
            throw new \Core\Exceptions\NotFoundError("Controller \"" . $name . "\" not found.");
        }
        return $this->_controllers[$name];
    }
}

?>