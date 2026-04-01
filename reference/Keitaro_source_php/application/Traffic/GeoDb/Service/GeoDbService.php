<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\GeoDb\Service;

class GeoDbService extends \Traffic\Service\AbstractService
{
    public function update(\Component\GeoDb\AbstractGeoDb $db)
    {
        $db->manager()->update();
    }
    public function delete(\Component\GeoDb\AbstractGeoDb $db)
    {
        $db->manager()->delete();
    }
    public function updateMany($dbs)
    {
        $errors = [];
        foreach ($dbs as $db) {
            if ($db->isUpdateAvailable()) {
                try {
                    $this->update($db);
                } catch (\Component\GeoDb\Error\DbUpdateError $e) {
                    $errors[] = $e->getMessage();
                }
            }
        }
        if (!empty($errors)) {
            throw new \Component\GeoDb\Error\DbUpdateError(implode("; ", $errors));
        }
    }
    public function settings()
    {
        $value = \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::IPDB);
        if (!empty($value)) {
            return json_decode($value, true);
        }
        return [];
    }
    public function setDbForDataType($dataType, $dbId)
    {
        $settings = $this->settings();
        $settings[$dataType] = $dbId;
        \Traffic\Service\SettingsService::instance()->updateValue(\Traffic\Model\Setting::IPDB, json_encode($settings));
    }
    public function saveSettings($settings)
    {
        if (!is_array($settings)) {
            throw new \Exception("Trying to save incorrect settings: " . json_encode($settings));
        }
        \Traffic\Service\SettingsService::instance()->updateValue(\Traffic\Model\Setting::IPDB, json_encode($settings));
        return $this->settings();
    }
    public function info(\Component\GeoDb\AbstractGeoDb $db, $ip)
    {
        if ($db->exists()) {
            return $db->adapter()->info($ip);
        }
        return [];
    }
    public function rawInfo(\Component\GeoDb\AbstractGeoDb $db, $ip)
    {
        try {
            return $db->adapter()->rawInfo($ip);
        } catch (\Component\GeoDb\Error\DbNotFound $exception) {
            \Traffic\Logging\Service\LoggerService::instance()->error("IGNORED: " . $exception->getMessage());
            return [];
        }
    }
    public function canDbResolveDataType($dataType, \Component\GeoDb\AbstractGeoDb $db, \Component\GeoDb\AbstractGeoDb $dbBySettings = NULL)
    {
        if (!in_array($dataType, $db->definition()->dataTypes())) {
            return false;
        }
        if (empty($dbBySettings)) {
            return true;
        }
        return empty($dbBySettings) || get_class($db->adapter()) === get_class($dbBySettings->adapter());
    }
}

?>