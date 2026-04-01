<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoProfiles\Controller;

class GeoProfilesController extends \Admin\Controller\BaseController
{
    public function listAsOptionsAction()
    {
        return $this->indexAction();
    }
    public function indexAction()
    {
        $profiles = \Component\GeoProfiles\Model\GeoProfile::fetchAll();
        return $this->serialize($profiles, new \Component\GeoProfiles\Serializer\DecoratedGeoProfileSerializer());
    }
    public function showAction()
    {
        $id = (int) $this->getParam("id");
        $profile = \Component\GeoProfiles\Model\GeoProfile::find($id);
        return $this->serialize($profile, new \Component\GeoProfiles\Serializer\DecoratedGeoProfileSerializer());
    }
    public function createAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        if ($this->isPost()) {
            $profile = \Component\GeoProfiles\Service\GeoProfileService::instance()->parseCountriesAndCreate($this->getPostParams());
            return $this->serialize($profile, new \Component\GeoProfiles\Serializer\DecoratedGeoProfileSerializer());
        }
    }
    public function updateAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        $id = (int) $this->getParam("id");
        $profile = \Component\GeoProfiles\Model\GeoProfile::find($id);
        if ($this->isPost()) {
            $profile = \Component\GeoProfiles\Service\GeoProfileService::instance()->parseCountriesAndUpdate($profile, $this->getPostParams());
            return $this->serialize($profile, new \Component\GeoProfiles\Serializer\DecoratedGeoProfileSerializer());
        }
    }
    public function deleteAction()
    {
        if (!$this->isAdmin() || \Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDeny();
        }
        $id = (int) $this->getParam("id");
        $profile = \Component\GeoProfiles\Model\GeoProfile::find($id);
        \Component\GeoProfiles\Service\GeoProfileService::instance()->delete($profile);
    }
}

?>