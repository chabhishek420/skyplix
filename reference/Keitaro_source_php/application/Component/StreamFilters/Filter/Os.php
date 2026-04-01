<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Os extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $os = $rawClick->getOs();
        $found = false;
        $payload = $filter->getPayload();
        if (!is_array($payload)) {
            $payload = [$payload];
        }
        if (!empty($os)) {
            $os .= " " . $rawClick->getOsVersion();
        }
        foreach ($payload as $string) {
            if ($this->_findInWithRegexSupport($os, $string, false)) {
                $found = true;
                return $found && $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT || !$found && $filter->getMode() == \Traffic\Model\StreamFilter::REJECT;
            }
        }
    }
}

?>