<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\AdminApi;

class AdminApiRoutesRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_routers = [];
    private $_requiredParams = ["method", "route", "onMatch", "desc"];
    public function load()
    {
        foreach (\Core\ComponentManager\ComponentManager::instance()->getComponents() as $component) {
            $component->loadApiRoutes($this);
        }
    }
    public function register($route)
    {
        foreach ($this->_requiredParams as $checkParam) {
            if (empty($route[$checkParam])) {
                $json = json_encode($route);
                throw new AdminApiRouteError("Route '" . $json . "' must contain option " . $checkParam);
            }
        }
        $this->_routers[] = $route;
    }
    public function getRoutes()
    {
        return $this->_routers;
    }
}

?>