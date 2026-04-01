<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid;

class Dictionary
{
    private $_requestedBy = NULL;
    public function __construct(\Component\Users\Model\User $user)
    {
        $this->_requestedBy = $user;
    }
    public function get($name)
    {
        $method = "_" . \Traffic\Tools\Tools::toCamelCase($name, true);
        if (!method_exists($this, $method)) {
            throw new Error("Dictionary " . $name . " is not defined");
        }
        return call_user_func([$this, $method]);
    }
    private function _campaigns()
    {
        $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allActiveForList();
        $campaigns = \Component\Users\Service\AclService::instance()->filter($this->_requestedBy, $campaigns, \Component\Users\Service\AclService::VIEW, false);
        return \Component\Campaigns\Repository\CampaignRepository::instance()->listAsOptions($campaigns);
    }
    private function _countries()
    {
        $result = \Component\Clicks\Model\Ref\Country::fetchRows(NULL, NULL, "value");
        return $this->_prepare($result, function ($item) {
            if (empty($item["value"])) {
                return \Core\Locale\LocaleService::t("grid.unknown");
            }
            return \Component\GeoDb\Repository\CountriesRepository::instance()->getCountryName($item["value"]);
        });
    }
    private function _regions()
    {
        $items = \Component\Clicks\Model\Ref\Region::fetchRows(NULL, NULL, "value");
        $rows = [];
        foreach ($items as $item) {
            if (empty($item["value"])) {
                $name = \Core\Locale\LocaleService::t("grid.unknown");
            } else {
                $name = \Component\GeoDb\Repository\RegionsRepository::instance()->getRegionName($item["value"]);
            }
            $rows[] = ["value" => $item["id"], "name" => $name];
        }
        return $rows;
    }
    private function _cities()
    {
        $items = \Component\Clicks\Model\Ref\City::fetchRows(NULL, NULL, "value");
        $rows = [];
        foreach ($items as $item) {
            $name = $item["value"];
            if (empty($name)) {
                $name = \Core\Locale\LocaleService::t("grid.unknown");
            }
            $rows[] = ["value" => $item["id"], "name" => $name];
        }
        return $rows;
    }
    private function _operators()
    {
        $items = \Component\Clicks\Model\Ref\Operator::fetchRows(NULL, NULL, "value");
        $rows = [];
        foreach ($items as $item) {
            if (empty($item["value"])) {
                $name = \Core\Locale\LocaleService::t("grid.unknown");
                $group = NULL;
            } else {
                $name = \Component\GeoDb\Repository\OperatorsRepository::instance()->getName($item["value"]);
                $country = substr($item["value"], -2, 2);
                $group = \Component\GeoDb\Repository\CountriesRepository::instance()->getCountryName($country);
            }
            $rows[] = ["value" => $item["id"], "name" => $name, "group" => $group];
        }
        return $rows;
    }
    private function _os()
    {
        $result = \Component\Clicks\Model\Ref\Os::fetchRows(NULL, NULL, "value");
        return $this->_prepare($result, function ($item) {
            if (empty($item["value"])) {
                return \Core\Locale\LocaleService::t("grid.unknown");
            }
            return $item["value"];
        });
    }
    private function _browsers()
    {
        $result = \Component\Clicks\Model\Ref\Browser::fetchRows(NULL, NULL, "value");
        return $this->_prepare($result, function ($item) {
            if (empty($item["value"])) {
                return \Core\Locale\LocaleService::t("grid.unknown");
            }
            return $item["value"];
        });
    }
    private function _deviceModels()
    {
        $result = \Component\Clicks\Model\Ref\DeviceModel::fetchRows(NULL, NULL, "value");
        return $this->_prepare($result, function ($item) {
            if (empty($item["value"])) {
                return \Core\Locale\LocaleService::t("grid.unknown");
            }
            return $item["value"];
        });
    }
    private function _deviceTypes()
    {
        $result = \Component\Clicks\Model\Ref\DeviceType::fetchRows(NULL, NULL, "value");
        return $this->_prepare($result, function ($item) {
            if (empty($item["value"])) {
                return \Core\Locale\LocaleService::t("grid.unknown");
            }
            return \Component\Device\Repository\DeviceTypeRepository::instance()->getName($item["value"]);
        });
    }
    private function _connectionTypes()
    {
        $result = \Component\Clicks\Model\Ref\ConnectionType::fetchRows(NULL, NULL, "value");
        return $this->_prepare($result, function ($item) {
            if (empty($item["value"])) {
                return \Core\Locale\LocaleService::t("grid.unknown");
            }
            return \Component\GeoDb\Repository\ConnectionTypesRepository::instance()->getName($item["value"]);
        });
    }
    private function _languages()
    {
        $result = \Component\Clicks\Model\Ref\Language::fetchRows("DISTINCT(`value`), id", NULL, "value");
        return $this->_prepare($result, function ($item) {
            if (empty($item["value"])) {
                return \Core\Locale\LocaleService::t("grid.unknown");
            }
            return \Component\Device\Repository\LanguagesRepository::instance()->getName($item["value"]);
        });
    }
    private function _isp()
    {
        $result = \Component\Clicks\Model\Ref\Isp::fetchRows(NULL, NULL, "value");
        return $this->_prepare($result, function ($item) {
            if (empty($item["value"])) {
                return \Core\Locale\LocaleService::t("grid.unknown");
            }
            return $item["value"];
        });
    }
    private function _prepare($items, $callback)
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = ["value" => $item["id"], "name" => $callback($item)];
        }
        return $rows;
    }
}

?>