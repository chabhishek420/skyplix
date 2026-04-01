<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Device\Repository;

class DeviceTypeRepository extends \Traffic\Repository\AbstractBaseRepository
{
    protected $_deviceTypes = NULL;
    public function getDeviceTypes()
    {
        return $this->_deviceTypes;
    }
    public function getDeviceTypesAsOptions($addBlank = false)
    {
        $items = [];
        if ($addBlank) {
            $items[] = ["key" => "", "name" => \Core\Locale\LocaleService::t("grid.all_devices")];
        }
        $items[] = ["key" => "@empty", "name" => \Core\Locale\LocaleService::t("devices.unknown")];
        foreach ($this->getDeviceTypes() as $type) {
            $items[] = ["key" => $type, "name" => $this->getName($type)];
        }
        return $items;
    }
    public function only($onlyItems)
    {
        $result = [];
        $items = $this->getDeviceTypesAsOptions();
        foreach ($items as $item) {
            if (in_array($item["key"], $onlyItems)) {
                $result[] = $item;
            }
        }
        return $result;
    }
    public function getName($key)
    {
        if ($key) {
            return \Core\Locale\LocaleService::t("devices." . $key);
        }
    }
    public function getAsNames($items)
    {
        $value = [];
        if ($items && is_array($items)) {
            foreach ($items as $name) {
                $value[] = \Core\Locale\LocaleService::t("devices." . $name);
            }
        }
        return $value;
    }
}

?>