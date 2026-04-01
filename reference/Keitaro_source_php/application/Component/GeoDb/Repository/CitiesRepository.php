<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Repository;

class CitiesRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_data = NULL;
    public function getData()
    {
        if (!isset($this->_data)) {
            $this->_data = (include ROOT . "/application/Component/GeoDb/dictionaries/cities.php");
        }
        return $this->_data;
    }
    public function search($query, $limit = 20)
    {
        if (empty($limit)) {
            $limit = 20;
        }
        $data = $this->getData();
        $items = [];
        if (empty($query)) {
            return $items;
        }
        $translit = \Traffic\Tools\Tools::translit($query);
        foreach ($data as $country => $data) {
            foreach ($data as $name) {
                if (mb_stripos($name, $query, NULL, "utf-8") === 0 || $translit && mb_stripos($name, $translit, NULL, "utf-8") === 0) {
                    $items[] = $name;
                    if ($limit <= count($items)) {
                        return $items;
                    }
                }
            }
        }
        return array_values(array_unique($items));
    }
}

?>