<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros;

class MacroRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_exclude = ["group_id", "country_code", "referer", "ua", "example", "keyword_cp1251", "keitaro_campaign_id", "keitaro_campaign_name", "country_name", "region_name", "se", "useragent"];
    private $_macroNames = ["click" => [], "conversion" => []];
    private $_macros = ["click" => [], "conversion" => []];
    private $_aliases = [];
    const DEFAULT_CUSTOM_PATH = "/application/macros";
    const CONVERSION = "conversion";
    const CLICK = "click";
    public function loadMacros()
    {
        if (!empty($this->_macros["click"])) {
            return NULL;
        }
        $this->register("sample", new Predefined\Sample());
        $this->register("random", new Predefined\Random());
        $this->register("from_file", new Predefined\FromFile());
        $this->register("date", new Predefined\Date());
        $this->register("device_type", new Predefined\DeviceType());
        $this->register("profit", new Predefined\Profit());
        $this->register("revenue", new Predefined\Revenue());
        $this->register("status", new Predefined\Status());
        $this->register("original_status", new Predefined\OriginalStatus());
        $this->register("tid", new Predefined\Tid());
        $this->register("cost", new Predefined\Cost());
        $this->register("conversion_cost", new Predefined\ConversionCost());
        $this->register("conversion_revenue", new Predefined\ConversionRevenue());
        $this->register("conversion_profit", new Predefined\ConversionProfit());
        $this->register("campaign_name", new Predefined\CampaignName());
        $this->alias("keitaro_campaign_id", "campaign_id");
        $this->alias("keitaro_campaign_name", "campaign_name");
        $this->alias("keitaro_landing_id", "landing_id");
        $this->alias("keitaro_offer_id", "offer_id");
        $this->register("operator", new Predefined\Operator());
        $this->alias("carrier", "operator");
        $this->register("connection_type", new Predefined\ConnectionType());
        $this->register("city", new Predefined\City());
        $this->alias("country_code", "country");
        $this->register("country", new Predefined\Country());
        $this->alias("country_name", "country");
        $this->register("ip", new Predefined\Ip());
        $this->register("region", new Predefined\Region());
        $this->alias("region_name", "region");
        $this->register("conversion_time", new Predefined\ConversionTime());
        $this->register("debug", new Predefined\Debug());
        $this->register("x_requested_with", new Predefined\XRequestedWith());
        $this->register("subid", new Predefined\Subid());
        $this->alias("sub_id", "subid");
        for ($i = 1; $i <= \Traffic\Repository\ParameterRepository::instance()->getSubIdCount(); $i++) {
            $param = "sub_id_" . $i;
            $this->register($param, new Predefined\AnyClickMacro($param));
        }
        for ($i = 1; $i <= \Traffic\Repository\ParameterRepository::instance()->getExtraParamCount(); $i++) {
            $param = "extra_param_" . $i;
            $this->register($param, new Predefined\AnyClickMacro($param));
        }
        $otherParams = ["se", "source", "ad_campaign_id", "external_id", "creative_id", "referrer", "landing_id", "ts_id", "offer_id", "campaign_id", "stream_id", "isp", "parent_campaign_id", "is_bot", "is_using_proxy", "search_engine", "browser", "browser_version", "os", "os_version", "language", "user_agent", "device_model", "device_brand", "destination", "token"];
        foreach ($otherParams as $param) {
            $this->register($param, new Predefined\AnyClickMacro($param));
        }
        $otherParams = ["visitor_id"];
        foreach ($otherParams as $param) {
            $this->register($param, new Predefined\AnyConversionMacro($param));
        }
        $this->register("keyword", new Predefined\Keyword(Predefined\Keyword::UTF8));
        $this->register("keyword_cp1251", new Predefined\Keyword(Predefined\Keyword::CP1251));
        $this->register("offer", new Predefined\Offer());
        $this->register("current_domain", new Predefined\CurrentDomain());
        $this->register("traffic_source_name", new Predefined\TrafficSourceName());
        $this->register("visitor_code", new Predefined\VisitorCode());
        $this->alias("referer", "referrer");
        $this->alias("se", "search_engine");
        $this->alias("ua", "user_agent");
        $this->alias("useragent", "user_agent");
        if (empty($customPath)) {
            $customPath = ROOT . DEFAULT_CUSTOM_PATH;
        }
        $this->loadCustomMacros($customPath);
    }
    public function register($name, $obj)
    {
        $type = $this->_findType($obj);
        if (empty($type)) {
            return NULL;
        }
        if (!empty($this->_macros[$type][$name])) {
            \Traffic\Logging\Service\LoggerService::instance()->debug("Macro with name " . $name . " is already exists. Please use another name.");
        } else {
            $this->_macros[$type][$name] = $obj;
            $this->_macroNames[$type][] = $name;
        }
    }
    public function alias($aliasName, $macroName)
    {
        if (!empty($this->_aliases[$aliasName])) {
            throw new \Core\Application\Exception\Error("Macro alias " . $aliasName . " is defined");
        }
        $this->_aliases[$aliasName] = $macroName;
    }
    public function getMacroNames($type = NULL)
    {
        $this->loadMacros();
        if (empty($type)) {
            return array_flatten($this->_macroNames);
        }
        return $this->_macroNames[$type];
    }
    public function getActiveMacroNames($filter = NULL)
    {
        $this->loadMacros();
        $names = $this->getMacroNames($filter);
        asort($names);
        return array_values(array_diff($names, $this->_exclude));
    }
    public function getClickMacros()
    {
        return $this->getMacros(CLICK);
    }
    public function getConversionMacros()
    {
        return $this->getMacros(CONVERSION);
    }
    public function getOnly($type)
    {
        $this->loadMacros();
        return $this->_macros[$type];
    }
    public function getMacros($type)
    {
        $this->loadMacros();
        return $this->_macros[$type];
    }
    public function getMacro($name)
    {
        $this->loadMacros();
        if (!empty($this->_aliases[$name])) {
            $name = $this->_aliases[$name];
        }
        foreach ($this->_macros as $type => $macros) {
            if (!empty($macros[$name])) {
                return $macros[$name];
            }
        }
    }
    public function isAlias($name)
    {
        return isset($this->_aliases[$name]);
    }
    public function getAliases($name)
    {
        $result = [];
        foreach ($this->_aliases as $alias => $macroName) {
            if ($macroName == $name) {
                $result[] = $alias;
            }
        }
        return $result;
    }
    public function loadCustomMacros($customPath)
    {
        if (!\Core\Application\FeatureService::instance()->hasExtensionsFeature()) {
            return NULL;
        }
        $customMacroArray = \Component\Macros\Repository\CustomMacroRepository::instance()->getCustomMacros($customPath, $this->_macroNames);
        foreach ($customMacroArray as $name => $macro) {
            $this->register($name, $macro);
        }
    }
    private function _findType($obj)
    {
        $type = \Traffic\BackCompatibility\BackCompatibility::getMacroType($obj);
        if (!empty($type)) {
            return $type;
        }
        if ($obj instanceof AbstractClickMacro) {
            if ($obj instanceof AbstractConversionMacro) {
                $name = get_class($obj);
                \Traffic\Logging\Service\LoggerService::instance()->info("Macro '" . $name . "' is incorrect. It must be inherited of AbstractClickMacro or AbstractConversionMacro");
            } else {
                $type = CONVERSION;
            }
        } else {
            $type = CLICK;
        }
        return $type;
    }
}

?>