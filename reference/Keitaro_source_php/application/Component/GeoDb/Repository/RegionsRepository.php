<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Repository;

class RegionsRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_data = [];
    const COUNTRY = "country";
    const REGION = "region";
    protected function _dataToKey($country, $regionCode)
    {
        $res = "";
        if (!empty($country)) {
            $res .= $country . "_";
        }
        $res .= $regionCode;
        return $res;
    }
    protected function _keyToData($regionCode)
    {
        $sep = NULL;
        if (strstr($regionCode, "-")) {
            $sep = "-";
        } else {
            if (strstr($regionCode, "_")) {
                $sep = "_";
            }
        }
        if (!empty($sep)) {
            list($country, $regionCode) = explode($sep, $regionCode);
        } else {
            $country = NULL;
        }
        return [COUNTRY => $country, REGION => $regionCode];
    }
    public function getData()
    {
        $db = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->getDbForDataType(\Traffic\GeoDb\IpInfoType::REGION);
        $path = $db->definition()->dictionariesPath();
        if (!isset($this->_data[$path])) {
            $this->_data[$path] = (include $path . "/regions.php");
        }
        return $this->_data[$path];
    }
    public function getRegionName($regionCode = NULL, $lang = NULL)
    {
        $country = NULL;
        $regionCode = strtoupper($regionCode);
        if (empty($regionCode) || $regionCode == "@empty") {
            return \Core\Locale\LocaleService::t("grid.unknown");
        }
        if (strstr($regionCode, "_")) {
            list($country, $regionCode) = explode("_", $regionCode);
        }
        $info = $this->findByCountryAndRegion($country, $regionCode, $lang);
        if (isset($info)) {
            return $info["name"];
        }
        return $regionCode;
    }
    public function findByCountryAndRegion($country, $regionCode, $lang = NULL)
    {
        if (empty($lang)) {
            $lang = \Core\Locale\LocaleService::instance()->getLanguage();
        }
        $data = $this->getData();
        if (!$regionCode) {
            return NULL;
        }
        if (isset($data[$country]) && isset($data[$country][$regionCode])) {
            return ["key" => $this->_dataToKey($country, $regionCode), "country" => $country, "code" => $regionCode, "name" => $data[$country][$regionCode][$lang]];
        }
    }
    public function getRegionData($query)
    {
        $data = $this->getData();
        foreach ($data as $country => $data) {
            foreach ($data as $code => $name) {
                if ($name["en"] == $query || mb_strtolower($name["ru"], "utf-8") == mb_strtolower($query, "utf-8")) {
                    return ["country" => $country, "code" => $code];
                }
            }
        }
    }
    public function search($query, $onlyFirst = false, $limit = 10)
    {
        $data = $this->getData();
        $items = [];
        if (empty($query)) {
            return $items;
        }
        foreach ($data as $country => $data1) {
            foreach ($data1 as $region => $name) {
                if (stristr($name["en"], $query) || mb_stristr($name["ru"], $query, 0, "utf-8")) {
                    $item = ["key" => $this->_dataToKey($country, $region), "name" => $name[\Core\Locale\LocaleService::instance()->getLanguage()], "country" => $country];
                    if ($onlyFirst) {
                        return $item;
                    }
                    $items[] = $item;
                    if ($limit <= count($item)) {
                        return $items;
                    }
                }
            }
        }
        return $onlyFirst ? NULL : $items;
    }
    public function only($items, $limit = 10)
    {
        if (empty($limit)) {
            $limit = 10;
        }
        $result = [];
        foreach ($items as $row) {
            $data = $this->_keyToData($row);
            $info = $this->findByCountryAndRegion($data[COUNTRY], $data[REGION]);
            if (isset($info)) {
                $result[] = $info;
            }
            if ($limit <= count($result)) {
                return $result;
            }
        }
        return $result;
    }
    public function nameToString($name)
    {
        $info = $this->search($name, true);
        if (isset($info)) {
            return $info["key"];
        }
    }
}

?>