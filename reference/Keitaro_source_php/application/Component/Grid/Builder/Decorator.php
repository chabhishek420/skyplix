<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Builder;

class Decorator
{
    private $_columns = NULL;
    private $_definition = NULL;
    const CITY = "_cityName";
    const REGION_NAME = "_regionName";
    const OPERATOR_NAME = "_operatorName";
    const CONNECTION_TYPE_NAME = "_connectionTypeName";
    const DEVICE_TYPE_NAME = "_deviceTypeName";
    const COUNTRY_NAME = "_countryName";
    const IP = "_ip";
    const LANGUAGE = "_language";
    const BROWSER_ICON = "_browserIcon";
    const OS_ICON = "_osIcon";
    const DECIMAL = "_decimal";
    const WEEK = "_week";
    const TRAFFIC_SOURCE = "_trafficSource";
    const OFFER = "_offer";
    const LANDING = "_landing";
    const AFFILIATE_NETWORK = "_affiliateNetwork";
    const PARSE_JSON = "_parseJson";
    const AD_CAMPAIGN_ID = "_adCampaignId";
    const CREATIVE_ID = "_creativeId";
    const MASK1 = "_mask1";
    const MASK2 = "_mask2";
    const STREAM = "_stream";
    public function __construct($columns, \Component\Grid\Definition\GridDefinition $definition)
    {
        $this->_columns = $columns;
        $this->_definition = $definition;
    }
    public function decorateRows($rows)
    {
        $newRows = [];
        foreach ($rows as $row) {
            $newRow = $this->decorateRow($row);
            $newRows[] = $newRow;
        }
        return $newRows;
    }
    public function decorateRow($row)
    {
        if (empty($row)) {
            return [];
        }
        $newRow = [];
        foreach ($this->_columns as $columnName) {
            $column = $this->_definition->getColumn($columnName);
            if ($column->getDecorator()) {
                $newRow[$columnName] = call_user_func([$this, $column->getDecorator()], $row, $columnName);
            } else {
                if (array_key_exists($columnName, $row)) {
                    $newRow[$columnName] = $row[$columnName];
                }
            }
        }
        return $newRow;
    }
    private function _regionName($row)
    {
        if (!empty($row["region"])) {
            return \Component\GeoDb\Repository\RegionsRepository::instance()->getRegionName($row["region"]);
        }
        return \Core\Locale\LocaleService::t("grid.unknown");
    }
    private function _cityName($row)
    {
        if (empty($row["city"])) {
            return \Core\Locale\LocaleService::t("grid.unknown");
        }
        return $row["city"];
    }
    private function _operatorName($row)
    {
        if (!empty($row["operator"])) {
            return \Component\GeoDb\Repository\OperatorsRepository::instance()->getName($row["operator"]);
        }
        return \Core\Locale\LocaleService::t("grid.unknown");
    }
    private function _connectionTypeName($row)
    {
        if (!empty($row["connection_type"])) {
            return \Component\GeoDb\Repository\ConnectionTypesRepository::instance()->getName($row["connection_type"]);
        }
        return \Core\Locale\LocaleService::t("grid.unknown");
    }
    private function _deviceTypeName($row)
    {
        if (!empty($row["device_type"])) {
            return \Component\Device\Repository\DeviceTypeRepository::instance()->getName($row["device_type"]);
        }
        return \Core\Locale\LocaleService::t("grid.unknown");
    }
    private function _countryName($row)
    {
        if (!empty($row["country"])) {
            return \Component\GeoDb\Repository\CountriesRepository::instance()->getCountryName($row["country"]);
        }
        return \Core\Locale\LocaleService::t("grid.unknown");
    }
    private function _ip($row)
    {
        if (!empty($row["ip"])) {
            return long2ip((int) $row["ip"]);
        }
        return "[IPv6]";
    }
    private function _language($row)
    {
        if (!empty($row["language"])) {
            return \Component\Device\Repository\LanguagesRepository::instance()->getName($row["language"]);
        }
        return \Core\Locale\LocaleService::t("grid.unknown");
    }
    private function _browserIcon($row)
    {
        return \Component\Device\Repository\BrowsersRepository::instance()->getCode($row["browser_icon"]);
    }
    private function _osIcon($row)
    {
        return \Component\Device\Repository\OsRepository::instance()->getCode($row["os_icon"]);
    }
    private function _trafficSource($row)
    {
        if (empty($row["ts"])) {
            return \Core\Locale\LocaleService::t("grid.not_set");
        }
        return $row["ts"];
    }
    private function _affiliateNetwork($row)
    {
        if (empty($row["affiliate_network"])) {
            return \Core\Locale\LocaleService::t("grid.not_set");
        }
        return $row["affiliate_network"];
    }
    private function _offer($row)
    {
        if (empty($row["offer"])) {
            return "";
        }
        return $row["offer"];
    }
    private function _landing($row)
    {
        if (empty($row["landing"])) {
            return \Core\Locale\LocaleService::t("grid.not_set");
        }
        return $row["landing"];
    }
    private function _adCampaignId($row)
    {
        if ($row["ad_campaign_id"] === "") {
            return \Core\Locale\LocaleService::t("grid.not_set");
        }
        return $row["ad_campaign_id"];
    }
    private function _creativeId($row)
    {
        if ($row["creative_id"] === "") {
            return \Core\Locale\LocaleService::t("grid.not_set");
        }
        return $row["creative_id"];
    }
    private function _decimal($row, $columnName)
    {
        if (is_null($row[$columnName])) {
            $row[$columnName] = 0;
        }
        return number_format($row[$columnName], 2, ".", "");
    }
    private function _week($row, $columnName)
    {
        $year = substr($row[$columnName], 0, 4);
        $week = substr($row[$columnName], 4, 2);
        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $start = $dto->format(\Core\Locale\LocaleService::t("format.date"));
        $dto->modify("+6 days");
        $end = $dto->format(\Core\Locale\LocaleService::t("format.date"));
        return $start . " - " . $end;
    }
    private function _mask1($row, $columnName)
    {
        $value = $row[$columnName];
        return $value . ".0.0 - " . $value . ".255.255";
    }
    private function _mask2($row, $columnName)
    {
        $value = $row[$columnName];
        return $value . ".0 - " . $value . ".255";
    }
    private function _parseJson($row, $columnName)
    {
        if (!empty($row[$columnName])) {
            return json_decode($row[$columnName]);
        }
    }
    private function _stream($row, $columnName)
    {
        if ($row[$columnName]) {
            return $row[$columnName];
        }
        return "empty name";
    }
}

?>