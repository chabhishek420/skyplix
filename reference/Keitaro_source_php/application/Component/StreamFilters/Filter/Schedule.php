<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Schedule extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.time";
    }
    private function _preparePayload($payload)
    {
        if (!isset($payload["intervals"])) {
            return ["intervals" => $payload, "timezone" => NULL];
        }
        return $payload;
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $payload = $this->_preparePayload($filter->getPayload());
        $result = false;
        if (!empty($payload["intervals"])) {
            foreach ($payload["intervals"] as $daySchedule) {
                if ($this->_isThatDayAndTime($rawClick->getDateTime(), $daySchedule, $payload["timezone"])) {
                    $result = true;
                }
            }
        }
        return $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && $result || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && !$result;
    }
    protected function _isThatDayAndTime(\DateTime $time, $daySchedule, $timezone)
    {
        if (!empty($timezone)) {
            $time = clone $time;
            $time->setTimezone(new \DateTimeZone($timezone));
        }
        $currentDay = $time->format("w");
        $currentTime = $time->format("H:i");
        $currentDay--;
        if ($currentDay == -1) {
            $currentDay = 6;
        }
        $this->_convertOldFormat($daySchedule);
        if ($daySchedule["day"][0] <= $daySchedule["day"][1]) {
            if ($currentDay < $daySchedule["day"][0] || $daySchedule["day"][1] < $currentDay) {
                return false;
            }
            if ($daySchedule["day"][0] < $currentDay && $currentDay < $daySchedule["day"][1]) {
                return true;
            }
        } else {
            if ($daySchedule["day"][1] < $currentDay && $currentDay < $daySchedule["day"][0]) {
                return false;
            }
            if ($currentDay < $daySchedule["day"][1] || $daySchedule["day"][0] < $currentDay) {
                return true;
            }
        }
        if (empty($daySchedule["time"])) {
            return true;
        }
        if ($daySchedule["day"][0] <= $daySchedule["day"][1]) {
            if (($currentDay != $daySchedule["day"][0] || $daySchedule["time"][0] <= $currentTime) && ($currentDay != $daySchedule["day"][1] || $currentTime <= $daySchedule["time"][1])) {
                return true;
            }
        } else {
            if (($currentDay != $daySchedule["day"][1] || $currentTime <= $daySchedule["time"][1]) && ($currentDay != $daySchedule["day"][0] || $daySchedule["time"][0] <= $currentTime)) {
                return true;
            }
        }
        return false;
    }
    protected function _convertOldFormat($daySchedule)
    {
        if (!is_array($daySchedule["day"])) {
            $daySchedule["day"] = [$daySchedule["day"], $daySchedule["day"]];
        }
    }
    public function getHeaderTemplate()
    {
        return "\n          <timezone-select ng-model=\"filter.payload.timezone\"></timezone-select>\n        ";
    }
}

?>