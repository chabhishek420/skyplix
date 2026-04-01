<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Service;

class CurrentUserService extends \Traffic\Service\AbstractService
{
    private $_user = NULL;
    const UTC = "utc";
    public function set(\Component\Users\Model\User $user)
    {
        $this->_user = $user;
    }
    public function logout()
    {
        $this->_user = NULL;
    }
    public function get()
    {
        return $this->_user;
    }
    public function signedIn()
    {
        return $this->_user;
    }
    public function exists()
    {
        return isset($this->_user);
    }
    public function getTimezone()
    {
        if ($this->exists()) {
            $timezoneName = \Component\Users\Repository\UserPreferenceRepository::instance()->get($this->_user, \Component\Users\Model\UserPreference::TIMEZONE);
        } else {
            $timezoneName = UTC;
        }
        return new \DateTimeZone($timezoneName);
    }
}

?>