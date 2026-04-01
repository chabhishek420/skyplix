<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Language extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $found = false;
        $payload = $filter->getPayload();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach ($payload as $string) {
            if (\Component\StreamFilters\Service\StreamFilterService::instance()->equalOrEmpty($rawClick->getLanguage(), $string)) {
                $found = true;
                return !($filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && !$found || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && $found);
            }
        }
    }
}

?>