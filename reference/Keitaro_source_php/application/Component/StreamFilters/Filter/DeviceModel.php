<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class DeviceModel extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $model = $rawClick->getDeviceModel();
        $found = false;
        $payload = $filter->getPayload();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach ($payload as $row) {
            if ($this->_findInWithRegexSupport($model, $row, false)) {
                $found = true;
                return !(!$found && $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT || $found && $filter->getMode() == \Traffic\Model\StreamFilter::REJECT);
            }
        }
    }
}

?>