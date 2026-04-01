<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Controller;

class UserPreferencesController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $preferences = \Component\Users\Repository\UserPreferenceRepository::instance()->getAll($this->getUser());
        return $this->serialize($preferences, new \Component\Users\Serializer\UserPreferenceSerializer());
    }
    public function getAction()
    {
        $prefName = $this->getParam("pref_name");
        return \Component\Users\Repository\UserPreferenceRepository::instance()->get($this->getUser(), $prefName);
    }
    public function setAction()
    {
        $preference = \Component\Users\Service\UserPreferenceService::instance()->update($this->getUser(), $this->getPostParam("pref_name"), $this->getPostParam("pref_value"));
        return $this->serialize($preference, new \Component\Users\Serializer\UserPreferenceSerializer());
    }
}

?>