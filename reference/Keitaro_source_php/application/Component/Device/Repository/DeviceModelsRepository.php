<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Device\Repository;

class DeviceModelsRepository extends \Traffic\Repository\AbstractBaseRepository
{
    public function getData()
    {
        $data = (include ROOT . "/application/Component/Device/dictionaries/device_models.php");
        return $data;
    }
    public function search($query, $limit = NULL)
    {
        if (!$query) {
            return [];
        }
        $data = $this->getData();
        $items = [];
        foreach ($data as $id => $name) {
            if (mb_stripos($name, $query, NULL, "utf-8") === 0) {
                $items[] = $name;
            }
            if ($limit && $limit < count($items)) {
                ksort($items);
                return $items;
            }
        }
    }
}

?>