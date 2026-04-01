<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Country extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.geo";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $country = $rawClick->getCountry();
        $found = false;
        $payload = $filter->getPayload();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach ($payload as $value) {
            $valueUC = strtoupper($value);
            if (in_array($value, \Component\StreamFilters\Service\StreamFilterService::instance()->getEmptyQueries()) && $country == "") {
                $found = true;
            } else {
                if ($country === $valueUC) {
                    $found = true;
                }
            }
            return !(!$found && $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT || $found && $filter->getMode() == \Traffic\Model\StreamFilter::REJECT);
        }
    }
}

?>