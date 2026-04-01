<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Repository;

class IspRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_data = NULL;
    public function getData()
    {
        if (!isset($this->_data)) {
            $this->_data = (include ROOT . "/application/Component/GeoDb/dictionaries/isp.php");
        }
        return $this->_data;
    }
    public function search($query, $limit = 20)
    {
        if (!$query) {
            return [];
        }
        $data = $this->getData();
        $items = [];
        if (empty($query)) {
            return $items;
        }
        $translit = \Traffic\Tools\Tools::translit($query);
        foreach ($data as $name) {
            if (mb_stripos($name, $query, NULL, "utf-8") === 0 || mb_stripos($name, $translit, NULL, "utf-8") === 0) {
                $items[] = $name;
                if ($limit <= count($items)) {
                    return $items;
                }
            }
        }
        return $items;
    }
}

?>