<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Region extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.geo";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $country = $rawClick->getCountry();
        $region = $rawClick->getRegion();
        $found = false;
        $strings = $filter->getPayload();
        foreach ($strings as $value) {
            if ($region == $value || $country . "_" . $region === $value || empty($region) && in_array($value, \Component\StreamFilters\Service\StreamFilterService::instance()->getEmptyQueries())) {
                $found = true;
                return $found && $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT || !$found && $filter->getMode() == \Traffic\Model\StreamFilter::REJECT;
            }
        }
    }
}

?>