<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class UserAgent extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $ua = $rawClick->getUserAgent();
        $found = false;
        $payload = $filter->getPayload();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach ($payload as $row) {
            if (\Component\StreamFilters\Service\StreamFilterService::instance()->findInWithRegexSupport($ua, $row, false)) {
                $found = true;
                return !($filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && !$found || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && $found);
            }
        }
    }
    public function getTemplate()
    {
        $str = "<stream-multi-value-input ng-model=\"filter.payload\" separators=\"['##']\" \n        field-name=\"'user_agent'\"></stream-multi-value-input>";
        return $str;
    }
}

?>