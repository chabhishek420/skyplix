<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Controller;

class GeoDbsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $dbs = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->all();
        $checkUpdates = true;
        return $this->serialize($dbs, new \Component\GeoDb\Serializer\GeoDbSerializer($checkUpdates));
    }
    public function settingsAction()
    {
        return \Traffic\GeoDb\Service\GeoDbService::instance()->settings();
    }
    public function saveSettingsAction()
    {
        return \Traffic\GeoDb\Service\GeoDbService::instance()->saveSettings($this->getPostParam("settings"));
    }
    public function updateAction()
    {
        $id = $this->getPostParam("id");
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        $db = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->getDb($id);
        \Traffic\GeoDb\Service\GeoDbService::instance()->update($db);
        return $this->serialize($db, new \Component\GeoDb\Serializer\GeoDbSerializer(true));
    }
}

?>