<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class ParameterRepository extends AbstractBaseRepository
{
    private $_cache = [];
    const EXTRA_PARAM_COUNT = 10;
    const SUB_ID_COUNT = 15;
    const X_REQUESTED_WITH_ID = "x_requested_with_id";
    const SUB_ID_15_ID = "sub_id_15_id";
    const AFFILIATE_NETWORK_ID = "affiliate_network_id";
    const SUBID_LIMIT = 30;
    public function clearCache()
    {
        $this->_cache = [];
    }
    public static function getAvailableParameters()
    {
        $result = ["keyword", "cost", "currency", "external_id", "creative_id", "ad_campaign_id", "source", "sub_id_1", "sub_id_2", "sub_id_3", "sub_id_4", "sub_id_5", "sub_id_6", "sub_id_7", "sub_id_8", "sub_id_9", "sub_id_10"];
        if (ParameterRepository::instance()->hasSubId15()) {
            $result = array_merge($result, ["sub_id_11", "sub_id_12", "sub_id_13", "sub_id_14", "sub_id_15"]);
        }
        $result = array_merge($result, ["extra_param_1", "extra_param_2", "extra_param_3", "extra_param_4", "extra_param_5", "extra_param_6", "extra_param_7", "extra_param_8", "extra_param_9", "extra_param_10"]);
        return $result;
    }
    public static function getDefaults()
    {
        $result = [];
        if (ParameterRepository::instance()->hasSubId15()) {
            $result = array_merge($result, ["sub_id_11" => "subid11", "sub_id_12" => "subid12", "sub_id_13" => "subid13", "sub_id_14" => "subid14", "sub_id_15" => "subid15"]);
        }
        $result = array_merge($result, ["extra_param_1" => "", "extra_param_2" => "", "extra_param_3" => "", "extra_param_4" => "", "extra_param_5" => "", "extra_param_6" => "", "extra_param_7" => "", "extra_param_8" => "", "extra_param_9" => "", "extra_param_10" => ""]);
        return $result;
    }
    public function getValue($name)
    {
        $setting = $name . "_aliases";
        if ($value = CachedSettingsRepository::instance()->get($setting)) {
            return $value;
        }
        $value = \Traffic\Service\ConfigService::instance()->get($name . "_names");
        if (!$value) {
            $defaults = $this->getDefaults();
            return isset($defaults[$name]) ? $defaults[$name] : "";
        }
    }
    public function getAliasesFor($name)
    {
        if (!isset($this->_cache[$name])) {
            $aliases = $this->getValue($name);
            $aliases = explode(",", $aliases);
            array_unshift($aliases, $name);
            $items = [];
            foreach ($aliases as $alias) {
                if (!empty($alias)) {
                    $items[] = trim($alias);
                }
            }
            $this->_cache[$name] = $items;
            unset($items);
            unset($aliases);
        }
        return $this->_cache[$name];
    }
    public function getAliasesForId()
    {
        $names = $this->getAliasesFor("id");
        $names[] = "campaign";
        return $names;
    }
    public function getAliasesForKeyword()
    {
        return $this->getAliasesFor("keyword");
    }
    public function getAliasesForSeReferrer()
    {
        return $this->getAliasesFor("se_referrer");
    }
    public function getAliasesForSource()
    {
        return $this->getAliasesFor("source");
    }
    public function getAliasesForReferrer()
    {
        return $this->getAliasesFor("referrer");
    }
    public function getSubIdCount()
    {
        if (ParameterRepository::instance()->hasSubId15()) {
            return SUB_ID_COUNT;
        }
        return 10;
    }
    public function getExtraParamCount()
    {
        if (CachedSettingsRepository::instance()->get("show_extra_param", false)) {
            return EXTRA_PARAM_COUNT;
        }
        return 0;
    }
    public function hasSubId15()
    {
        return $this->isColumnExists(SUB_ID_15_ID);
    }
    public function hasXRequestedWith()
    {
        return $this->isColumnExists(X_REQUESTED_WITH_ID);
    }
    public function hasAffiliateNetworkId()
    {
        return $this->isColumnExists(AFFILIATE_NETWORK_ID);
    }
    public function isColumnExists($column)
    {
        return CachedSettingsRepository::instance()->get("clicks_" . $column . "_exists");
    }
    public function filterClick(\Traffic\Model\Click $click)
    {
        if (!$this->hasXRequestedWith()) {
            $click->unset_value(X_REQUESTED_WITH_ID);
        }
        if (!$this->hasAffiliateNetworkId()) {
            $click->unset_value(AFFILIATE_NETWORK_ID);
        }
        if (!$this->hasSubId15()) {
            $click->unset_value("sub_id_11_id");
            $click->unset_value("sub_id_12_id");
            $click->unset_value("sub_id_13_id");
            $click->unset_value("sub_id_14_id");
            $click->unset_value("sub_id_15_id");
        }
        return $click;
    }
    public function filterConversion(\Traffic\Model\Conversion $conversion)
    {
        if (!\Component\Conversions\Service\ConversionService::instance()->hasXRequestedWith()) {
            $conversion->unset_value(X_REQUESTED_WITH_ID);
        }
        if (!\Component\Conversions\Service\ConversionService::instance()->hasAffiliateNetworkId()) {
            $conversion->unset_value(AFFILIATE_NETWORK_ID);
        }
        if (!\Component\Conversions\Service\ConversionService::instance()->hasSubId15()) {
            $conversion->unset_value("sub_id_11_id");
            $conversion->unset_value("sub_id_12_id");
            $conversion->unset_value("sub_id_13_id");
            $conversion->unset_value("sub_id_14_id");
            $conversion->unset_value("sub_id_15_id");
        }
        return $conversion;
    }
}

?>