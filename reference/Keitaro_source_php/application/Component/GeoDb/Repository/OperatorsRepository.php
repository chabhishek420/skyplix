<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Repository;

class OperatorsRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_cache = NULL;
    public function getData()
    {
        if (!isset($this->_cache)) {
            $this->_cache = (require ROOT . "/application/Component/GeoDb/dictionaries/operatorsV3.php");
        }
        return $this->_cache;
    }
    public function getOperators($addBlank = false, $only = NULL)
    {
        $items = [];
        if (!empty($only) && !is_array($only)) {
            $only = explode(",", $only);
        }
        foreach ($this->getData() as $key => $data) {
            if (empty($only) || in_array($key, $only)) {
                $items[] = ["key" => $key, "name" => $data[\Core\Locale\LocaleService::instance()->getLanguage()], "country" => strtoupper($data["country"])];
            }
        }
        $items = \Traffic\Tools\Tools::sortCollection($items, "name");
        if ($addBlank) {
            array_unshift($items, ["key" => "", "name" => \Core\Locale\LocaleService::t("grid.all_operators")]);
        }
        return $items;
    }
    public function search($query)
    {
        if (empty($query)) {
            return [];
        }
        $items = [];
        foreach ($this->getData() as $key => $data) {
            if (mb_stripos($data["en"], $query, NULL, "utf-8") === 0 || mb_stripos($data["ru"], $query, NULL, "utf-8") === 0 || $data["country"] == $query) {
                $items[] = ["key" => $key, "name" => $data[\Core\Locale\LocaleService::instance()->getLanguage()], "country" => strtoupper($data["country"])];
            }
        }
        return $items;
    }
    public function getName($key, $lang = NULL)
    {
        if (empty($lang)) {
            $lang = \Core\Locale\LocaleService::instance()->getLanguage();
        }
        if (empty($key)) {
            return $this->_find("@empty", $lang);
        }
        return $this->_find($key, $lang);
    }
    private function _find($key, $lang)
    {
        $data = $this->getData();
        if (isset($data[$key])) {
            $row = $data[$key];
        }
        if (!empty($row)) {
            return $row[$lang];
        }
        return $key;
    }
}

?>