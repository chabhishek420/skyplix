<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\AdminRequest;

class AdminRequest
{
    private $_controller = NULL;
    private $_action = NULL;
    const SYSTEM_CONTROLLER = "system";
    const DENY_ACTION = "deny";
    const CHANGE_LICENSE_KEY_ACTION = "changeLicenseKey";
    const ADD_LICENSE_KEY_ACTION = "addLicenseKey";
    const LOAD_LANGUAGE_KEY_ACTION = "loadLanguage";
    const LOAD_LICENSE_INFO_ACTION = "licenseInfo";
    const HOME_CONTROLLER = "home";
    const AUTH_CONTROLLER = "auth";
    const INDEX_ACTION = "index";
    private function _getSkippedActions()
    {
        return [[SYSTEM_CONTROLLER, CHANGE_LICENSE_KEY_ACTION], [SYSTEM_CONTROLLER, ADD_LICENSE_KEY_ACTION], [SYSTEM_CONTROLLER, LOAD_LANGUAGE_KEY_ACTION], [SYSTEM_CONTROLLER, LOAD_LICENSE_INFO_ACTION]];
    }
    private function _getGuestActions()
    {
        return [[SYSTEM_CONTROLLER, ADD_LICENSE_KEY_ACTION], [SYSTEM_CONTROLLER, LOAD_LANGUAGE_KEY_ACTION], [SYSTEM_CONTROLLER, LOAD_LICENSE_INFO_ACTION]];
    }
    public function getController()
    {
        return $this->_controller;
    }
    public function setController($controller)
    {
        $this->_controller = $controller;
        return $this;
    }
    public function getAction()
    {
        return $this->_action;
    }
    public function setAction($action)
    {
        $this->_action = $action;
        return $this;
    }
    public function isAuthController()
    {
        return $this->getController() == "auth";
    }
    public function isLicenseMustBeChecked()
    {
        if ($this->isAuthController()) {
            $count = \Component\Users\Repository\UserRepository::instance()->getAdminCount();
            return 0 >= $count;
        }
        foreach ($this->_getSkippedActions() as $path) {
            if ($this->getController() == $path[0] && $this->getAction() == $path[1]) {
                return false;
            }
        }
        return true;
    }
    public function isGuestAllowedActions($isGuestAllowedActions)
    {
        foreach ($this->_getGuestActions() as $path) {
            if ($this->getController() == $path[0] && $this->getAction() == $path[1]) {
                return true;
            }
        }
        return false;
    }
}

?>