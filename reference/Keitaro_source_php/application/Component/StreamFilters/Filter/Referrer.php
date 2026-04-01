<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Referrer extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.parameters";
    }
    public function getTooltip()
    {
        return \Core\Locale\LocaleService::t("filters.referrer_comment");
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $referer = $rawClick->getReferrer();
        $found = false;
        $values = $filter->getPayload();
        if (!is_array($values)) {
            return true;
        }
        foreach ($values as $row) {
            if (\Component\StreamFilters\Service\StreamFilterService::instance()->findInWithRegexSupport($referer, $row, false)) {
                $found = true;
                return !($filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && !$found || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && $found);
            }
        }
    }
    public function getTemplate()
    {
        $str = "<stream-multi-value-input\n         ng-model=\"filter.payload\"\n         field-name=\"'referrer'\"\n         ></stream-multi-value-input>";
        return $str;
    }
}

?>