<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\GeoDb\Repository;

class GeoDbRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_dbs = [];
    public function __construct()
    {
        $this->init();
    }
    public function init()
    {
        $this->addDb(new \Component\GeoDb\Ip2Location\Ip2LocationDb3Lite());
        $this->addDb(new \Component\GeoDb\Ip2Location\Ip2LocationDb3Full());
        $this->addDb(new \Component\GeoDb\Ip2Location\Ip2LocationDb4());
        $this->addDb(new \Component\GeoDb\Ip2Location\Ip2LocationPx2());
        $this->addDb(new \Component\GeoDb\Sypex\SypexCityLite());
        $this->addDb(new \Component\GeoDb\Sypex\SypexCityFull());
        $this->addDb(new \Component\GeoDb\Maxmind\MaxmindCityLite());
        $this->addDb(new \Component\GeoDb\Maxmind\MaxmindCityFull());
        $this->addDb(new \Component\GeoDb\Maxmind\MaxmindCountryFull());
        $this->addDb(new \Component\GeoDb\Maxmind\MaxmindIsp());
        $this->addDb(new \Component\GeoDb\Maxmind\MaxmindConnectionType());
        $this->addDb(new \Component\GeoDb\Keitaro\KeitaroCarrierDb());
        $this->addDb(new \Component\GeoDb\Keitaro\KeitaroBotDb2());
        $this->addDb(new \Component\GeoDb\Keitaro\InternalUserBotDb());
        $this->addDb(new \Component\GeoDb\ProIP\ProIPDBEssential());
    }
    public function all()
    {
        return $this->_dbs;
    }
    public function addDb(\Component\GeoDb\AbstractGeoDb $db)
    {
        $this->_dbs[$db->definition()->id()] = $db;
    }
    public function setDbs($dbs)
    {
        $this->_dbs = $dbs;
    }
    public function isAvailable($id)
    {
        return $this->getDb($id)->exists();
    }
    public function getDb($id)
    {
        if (!isset($this->_dbs[$id])) {
            throw new \Component\GeoDb\Error\DbError("Unknown geo db \"" . $id . "\" (available " . json_encode(array_keys($this->_dbs)) . ")");
        }
        return $this->_dbs[$id];
    }
    public function getDbInfo($id)
    {
        if (!isset($this->_dbs[$id])) {
            throw new \Component\GeoDb\Error\DbError("Unknown db repository \"" . $id . "\"");
        }
        return $this->_dbs[$id];
    }
    public function isDataTypeAvailable($dataType)
    {
        foreach ($this->all() as $db) {
            if (in_array($dataType, $db->definition()->dataTypes())) {
                return true;
            }
        }
        return false;
    }
    public function getDbForDataType($dataType)
    {
        $settings = \Traffic\GeoDb\Service\GeoDbService::instance()->settings();
        if (!in_array($dataType, \Traffic\GeoDb\IpInfoType::all())) {
            throw new \Component\GeoDb\Error\DbError("Incorrect data type '" . $dataType . "''");
        }
        if (isset($settings[$dataType])) {
            return $this->getDb($settings[$dataType]);
        }
        return NULL;
    }
}

?>