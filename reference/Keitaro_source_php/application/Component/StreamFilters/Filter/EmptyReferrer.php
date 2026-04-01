<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class EmptyReferrer extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.parameters";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $referrer = $rawClick->getReferrer();
        return $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && empty($referrer) || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && !empty($referrer);
    }
    public function getTemplate()
    {
        return "";
    }
}

?>