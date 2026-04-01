<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Repository;

class ConnectionTypesRepository extends \Traffic\Repository\AbstractBaseRepository
{
    const CELLULAR = "Cellular";
    const DIALUP = "Dialup";
    const CABLE = "Cable/DSL";
    const CORPORATE = "Corporate";
    const WIFI = "Wifi";
    public function all()
    {
        return ["Cellular", "Dialup", "Cable/DSL", "Corporate", "Wifi"];
    }
    public function getData()
    {
        if (!isset($this->_cache)) {
            $this->_cache = (require ROOT . "/application/Component/GeoDb/dictionaries/connection_types.php");
        }
        return $this->_cache;
    }
    public function allAsOptions($addBlank = false, $only = NULL, $lang = NULL)
    {
        if (!empty($only) && !is_array($only)) {
            $only = explode(",", $only);
        }
        if (empty($lang)) {
            $lang = \Core\Locale\LocaleService::instance()->getLanguage();
        }
        $items = [];
        $maxmindDbExists = \Traffic\GeoDb\Repository\GeoDbRepository::instance()->isAvailable(\Component\GeoDb\Maxmind\MaxmindConnectionType::ID);
        foreach ($this->getData() as $key => $info) {
            if (empty($only) || in_array($key, $only) && !isset($info["maxmind"]) || $maxmindDbExists) {
                $items[] = ["key" => $key, "name" => $info[$lang]];
            }
        }
        if ($addBlank) {
            array_unshift($items, ["value" => "", "name" => \Core\Locale\LocaleService::t("grid.all")]);
        }
        return $items;
    }
    public function getName($key, $lang = NULL)
    {
        if (empty($lang)) {
            $lang = \Core\Locale\LocaleService::instance()->getLanguage();
        }
        if (empty($key)) {
            $key = "@empty";
        }
        $data = $this->getData();
        if (isset($data[$key])) {
            return $data[$key][$lang];
        }
        return $key;
    }
}

?>