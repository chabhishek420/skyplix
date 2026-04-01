<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class DeviceType extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $deviceType = $rawClick->getDeviceType();
        $found = false;
        if (empty($deviceType) && in_array("@empty", $filter->getPayload()) || in_array($rawClick->getDeviceType(), $filter->getPayload())) {
            $found = true;
        }
        return $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && $found || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && !$found;
    }
}

?>