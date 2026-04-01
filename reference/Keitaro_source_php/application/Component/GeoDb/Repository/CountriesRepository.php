<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Repository;

class CountriesRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_data = NULL;
    public function getData()
    {
        if (!isset($this->_data)) {
            $this->_data = (include ROOT . "/application/Component/GeoDb/dictionaries/countries.php");
        }
        return $this->_data;
    }
    public function getCountryName($country, $lang = NULL)
    {
        if (empty($lang)) {
            $lang = \Core\Locale\LocaleService::instance()->getLanguage();
        }
        $info = $this->findByCountryInfo($country, $lang);
        if (isset($info)) {
            return $info["name"];
        }
        return $this->_data["@empty"][$lang];
    }
    public function findByCountryInfo($country, $lang = NULL)
    {
        if (empty($lang)) {
            $lang = \Core\Locale\LocaleService::instance()->getLanguage();
        }
        $data = $this->getData();
        if (!$country) {
            return NULL;
        }
        if (is_array($country)) {
            $country = $country["key"];
        }
        if ($country != "@empty") {
            $country = strtoupper($country);
        }
        if (isset($data[$country])) {
            return ["key" => $country, "name" => $data[$country][$lang]];
        }
    }
    public function getCountries($addBlank = false, $excludeUnknown = false)
    {
        $data = $this->getData();
        $result = [];
        foreach ($data as $country => $names) {
            if ($country != "@empty") {
                $result[] = ["key" => $country, "name" => $names[\Core\Locale\LocaleService::instance()->getLanguage()]];
            }
        }
        usort($result, function ($a, $b) {
            return $a["name"] < $b["name"] ? -1 : 1;
        });
        if (!$excludeUnknown) {
            array_unshift($result, ["key" => "@empty", "name" => $data["@empty"][\Core\Locale\LocaleService::instance()->getLanguage()]]);
        }
        if ($addBlank) {
            array_unshift($result, ["key" => "", "name" => \Core\Locale\LocaleService::t("grid.all_countries")]);
        }
        return $result;
    }
    public function getNames($only)
    {
        if (!is_array($only)) {
            return [];
        }
        $result = [];
        foreach ($this->only($only) as $info) {
            $result[] = $info["name"];
        }
        return $result;
    }
    public function only($only)
    {
        if (!is_array($only)) {
            return [];
        }
        $result = [];
        foreach ($only as $row) {
            $info = $this->findByCountryInfo($row);
            if ($info) {
                $result[] = $info;
            }
        }
        return $result;
    }
    public function isValid($code)
    {
        return array_key_exists($code, $this->getData());
    }
}

?>