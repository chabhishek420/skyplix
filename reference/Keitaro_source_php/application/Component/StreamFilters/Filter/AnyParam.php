<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class AnyParam extends \Core\Filter\AbstractFilter
{
    private $_paramName = NULL;
    private $_group = NULL;
    const COLLAPSE_AFTER_CNT = 30;
    public function __construct($paramName, $group = "parameters")
    {
        $this->_paramName = $paramName;
        $this->_group = $group;
    }
    public function getKey()
    {
        return $this->_paramName;
    }
    public function getGroup()
    {
        return "filters.groups." . $this->_group;
    }
    public function getTooltip()
    {
        return \Core\Locale\LocaleService::t("filters.basic_filter_desc");
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $value = $rawClick->get($this->_paramName);
        $found = false;
        $checkWith = $filter->getPayload();
        if (!is_array($checkWith)) {
            $checkWith = [$checkWith];
        }
        foreach ($checkWith as $filterValue) {
            if (\Component\StreamFilters\Service\StreamFilterService::instance()->findInWithRegexSupport($value, $filterValue)) {
                $found = true;
                return !($filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && !$found || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && $found);
            }
        }
    }
    public function getTemplate()
    {
        return "<stream-multi-value-input ng-model=\"filter.payload\" field-name=\"'" . $this->_paramName . "'\"></stream-multi-value-input>";
    }
}

?>