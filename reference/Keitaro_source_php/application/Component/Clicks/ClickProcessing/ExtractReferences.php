<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class ExtractReferences
{
    private $_mapping = ["ref_creative_ids" => "creative_id", "ref_destinations" => "destination", "ref_browsers" => "browser", "ref_ips" => "ip", "ref_user_agents" => "user_agent", "ref_languages" => "language", "ref_browser_versions" => "browser_version", "ref_operators" => "operator", "ref_countries" => "country", "ref_regions" => "region", "ref_cities" => "city", "ref_device_types" => "device_type", "ref_device_models" => "device_model", "ref_connection_types" => "connection_type", "ref_isp" => "isp", "ref_os" => "os", "ref_os_versions" => "os_version", "ref_keywords" => "keyword", "ref_referrers" => "referrer", "ref_search_engines" => "search_engine", "ref_sources" => "source", "ref_external_ids" => "external_id", "ref_ad_campaign_ids" => "ad_campaign_id", "ref_sub_id_1" => "sub_id_1", "ref_sub_id_2" => "sub_id_2", "ref_sub_id_3" => "sub_id_3", "ref_sub_id_4" => "sub_id_4", "ref_sub_id_5" => "sub_id_5", "ref_sub_id_6" => "sub_id_6", "ref_sub_id_7" => "sub_id_7", "ref_sub_id_8" => "sub_id_8", "ref_sub_id_9" => "sub_id_9", "ref_sub_id_10" => "sub_id_10"];
    const VALUE_LIMIT = 255;
    public function __construct($ignoreEmpty = false)
    {
        $this->_ignoreEmpty = $ignoreEmpty;
        if (\Traffic\Repository\ParameterRepository::instance()->hasSubId15()) {
            $this->_mapping = array_merge($this->_mapping, ["ref_sub_id_11" => "sub_id_11", "ref_sub_id_12" => "sub_id_12", "ref_sub_id_13" => "sub_id_13", "ref_sub_id_14" => "sub_id_14", "ref_sub_id_15" => "sub_id_15"]);
        }
        if (\Traffic\Repository\ParameterRepository::instance()->hasXRequestedWith()) {
            $this->_mapping = array_merge($this->_mapping, ["ref_x_requested_with" => "x_requested_with"]);
        }
    }
    public function process($entries)
    {
        $definition = new \Component\Clicks\Grid\ClicksDefinition();
        foreach ($definition->getDictionaries() as $relation) {
            $container = new ExtractReferences\ReferenceAggregator($relation);
            $entries = $this->_prepare($entries, $relation, $container);
            $entries = $this->_assign($entries, $relation, $container);
        }
        return $entries;
    }
    private function _getMappedField(\Component\Grid\Definition\Relation $relation)
    {
        if (empty($this->_mapping[$relation->getName()])) {
            throw new \Core\Application\Exception\Error("Field not found for " . $relation->getName());
        }
        return $this->_mapping[$relation->getName()];
    }
    private function _getMappedValue(\Component\Grid\Definition\Relation $relation, $rawClick)
    {
        if (isset($this->_mapping[$relation->getName()]) && isset($rawClick[$this->_mapping[$relation->getName()]]) && !$this->_isEmpty($rawClick[$this->_mapping[$relation->getName()]])) {
            $value = $rawClick[$this->_mapping[$relation->getName()]];
            if ($relation->getName() == "ref_ips" && $value < 0) {
                $value = (int) sprintf("%u", $value);
            }
            return $value;
        }
        if ($relation->getName() == "ref_ips") {
            return 0;
        }
        return "";
    }
    private function _prepare($rawClicks, \Component\Grid\Definition\Relation $relation, ExtractReferences\ReferenceAggregator $container)
    {
        foreach ($rawClicks as $rawClick) {
            $value = $this->_getMappedValue($relation, $rawClick);
            if (!($this->_isEmpty($value) && $this->_ignoreEmpty)) {
                if (!$this->_isEmpty($value) || $relation->allowEmpty()) {
                    $container->add($value);
                }
            }
        }
        $container->loadIds();
        $container->commit();
        return $rawClicks;
    }
    private function _isEmpty($value)
    {
        return is_null($value) || $value === "";
    }
    private function _assign($rawClicks, \Component\Grid\Definition\Relation $relation, ExtractReferences\ReferenceAggregator $container)
    {
        for ($i = 0; $i < count($rawClicks); $i++) {
            $value = $this->_getMappedValue($relation, $rawClicks[$i]);
            if (!($this->_isEmpty($value) && $this->_ignoreEmpty)) {
                if (!$this->_isEmpty($value) || $relation->allowEmpty()) {
                    $rawClicks[$i][$this->_getMappedField($relation) . "_id"] = $container->getId($value);
                }
            }
        }
        return $rawClicks;
    }
}

?>