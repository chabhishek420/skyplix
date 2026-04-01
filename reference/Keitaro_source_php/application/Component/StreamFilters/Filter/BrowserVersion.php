<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class BrowserVersion extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $version = $rawClick->getBrowserVersion();
        $payload = $filter->getPayload();
        $matcher = new \Component\StreamFilters\VersionMatcher();
        $match = $matcher->match($version, $payload["operator"], $payload["expression"]);
        return $match && $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT || !$match && $filter->getMode() == \Traffic\Model\StreamFilter::REJECT;
    }
    public function getTemplate()
    {
        $str = "<app-version-filter ng-model=\"filter.payload\"></app-version-filter>";
        return $str;
    }
}

?>