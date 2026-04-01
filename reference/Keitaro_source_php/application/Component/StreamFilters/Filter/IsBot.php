<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class IsBot extends \Core\Filter\AbstractFilter
{
    public function getKey()
    {
        return "bot";
    }
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        return $rawClick->isBot() && $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT || !$rawClick->isBot() && $filter->getMode() == \Traffic\Model\StreamFilter::REJECT;
    }
}

?>