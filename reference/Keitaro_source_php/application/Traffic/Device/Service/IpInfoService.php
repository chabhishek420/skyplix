<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Device\Service;

class IpInfoService extends \Traffic\Service\AbstractService
{
    public function getIpInfo($ip)
    {
        $result = [];
        $dbFields = $this->getDbSettings();
        foreach ($dbFields as $dbId => $fields) {
            $result = array_merge($result, $this->getDbInfo($ip, $dbId, $fields));
        }
        return $result;
    }
    public function getDbSettings()
    {
        $settings = \Traffic\GeoDb\Service\GeoDbService::instance()->settings();
        $dbFields = [];
        foreach ($settings as $dataType => $dbId) {
            if (!empty($dbId)) {
                if (!isset($dbFields[$dbId])) {
                    $dbFields[$dbId] = [];
                }
                $dbFields[$dbId][] = $dataType;
            }
        }
        return $dbFields;
    }
    public function getDbInfo($ip, $dbId, $fields)
    {
        $result = array_fill_keys($fields, NULL);
        $db = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->getDb($dbId);
        $info = \Traffic\GeoDb\Service\GeoDbService::instance()->info($db, $ip);
        if (!$db->exists()) {
            \Traffic\Logging\Service\LoggerService::instance()->error("Db " . $dbId . " does not exist");
            return $result;
        }
        if (empty($info)) {
            \Traffic\Logging\Service\LoggerService::instance()->error("Db " . $dbId . " returns non array result");
            return $result;
        }
        foreach ($fields as $f) {
            if (isset($info[$f])) {
                $result[$f] = $info[$f];
            }
        }
        return $result;
    }
}

?>