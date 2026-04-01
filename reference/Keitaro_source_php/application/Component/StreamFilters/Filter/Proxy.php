<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Proxy extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.geo";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $proxy = $rawClick->isUsingProxy();
        return $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && $proxy || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && !$proxy;
    }
    public function getTemplate()
    {
        return "";
    }
}

?>