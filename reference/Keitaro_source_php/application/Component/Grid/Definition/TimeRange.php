<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Definition;

class TimeRange
{
    private $_startTime = NULL;
    private $_endTime = NULL;
    private $_fieldName = NULL;
    private $_filtering = true;
    private $_timezone = NULL;
    const TODAY = "today";
    const YESTERDAY = "yesterday";
    const SEVEN_DAYS_AGO = "7_days_ago";
    const LAST_MONDAY = "last_monday";
    const ONE_MONTH_AGO = "1_month_ago";
    const PREVIOUS_MONTH = "previous_month";
    const FIRST_DAY_OF_THIS_MONTH = "first_day_of_this_month";
    const ONE_YEAR_AGO = "1_year_ago";
    const FIRST_DAY_OF_THIS_YEAR = "first_day_of_this_year";
    const ALL_TIME = "all_time";
    const CUSTOM_DATE_RANGE = "custom_date_range";
    const CUSTOM_TIME_RANGE = "custom_time_range";
    const INTERVAL = "interval";
    const FROM = "from";
    const TO = "to";
    const TIMEZONE = "timezone";
    const TIME_FORMAT = "Y-m-d H:i:s";
    public function __construct($range, $fieldName)
    {
        $this->_fieldName = $fieldName;
        if (!empty($range[TIMEZONE])) {
            $this->_timezone = new \DateTimeZone($range[TIMEZONE]);
        } else {
            $this->_timezone = new \DateTimeZone("UTC");
        }
        if (!empty($range[FROM]) || !empty($range[TO])) {
            $this->_parseTimes(isset($range[FROM]) ? $range[FROM] : NULL, isset($range[TO]) ? $range[TO] : NULL);
        } else {
            if (!empty($range[INTERVAL])) {
                $this->_parseInterval($range[INTERVAL]);
            }
        }
        return $this;
    }
    public function getStartTime()
    {
        return $this->_startTime;
    }
    public function getEndTime()
    {
        return $this->_endTime;
    }
    private function _parseInterval($interval)
    {
        if ($interval == ALL_TIME) {
            $this->_filtering = false;
        } else {
            switch ($interval) {
                case LAST_MONDAY:
                    $range = "monday this week";
                    $this->_startTime = new \DateTime($range, $this->getTimezone());
                    $this->_endTime = new \DateTime(NULL, $this->getTimezone());
                    break;
                case PREVIOUS_MONTH:
                    $this->_startTime = new \DateTime("first day of last month", $this->getTimezone());
                    $this->_endTime = new \DateTime("last day of last month", $this->getTimezone());
                    break;
                case FIRST_DAY_OF_THIS_YEAR:
                    $range = date("Y") . "-01-01";
                    $this->_startTime = new \DateTime($range, $this->getTimezone());
                    $this->_endTime = new \DateTime(NULL, $this->getTimezone());
                    break;
                case YESTERDAY:
                    $this->_startTime = new \DateTime($interval, $this->getTimezone());
                    $this->_endTime = new \DateTime("-1 day", $this->getTimezone());
                    break;
                default:
                    $range = str_replace("_", " ", $interval);
                    $this->_startTime = new \DateTime($range, $this->getTimezone());
                    $this->_endTime = new \DateTime(NULL, $this->getTimezone());
                    $this->_startTime->setTime(0, 0, 0);
                    $this->_endTime->setTime(23, 59, 59);
            }
        }
    }
    private function _parseTimes($from, $to)
    {
        if ($from) {
            $this->_startTime = new \DateTime($from, $this->getTimezone());
        }
        if ($to) {
            if (!strstr($to, " ") && !strstr($to, "T")) {
                $to .= " 23:59:59";
            }
            $this->_endTime = new \DateTime($to, $this->getTimezone());
        }
    }
    public function toFilter()
    {
        if ($this->_filtering) {
            if (!empty($this->_startTime) && !empty($this->_endTime)) {
                return $this->_buildBetweenFilter($this->_startTime, $this->_endTime);
            }
            if (!empty($this->_startTime) && empty($this->_endTime)) {
                return $this->_buildFromFilter($this->_startTime);
            }
            if (empty($this->_startTime) && !empty($this->_endTime)) {
                return $this->_buildToFilter($this->_endTime);
            }
        }
    }
    public function getTimezone()
    {
        return $this->_timezone;
    }
    private function _buildBetweenFilter(\DateTime $startTime, \DateTime $endTime)
    {
        $from = clone $startTime;
        $to = clone $endTime;
        $from = $from->setTimezone(new \DateTimeZone("UTC"))->format(TIME_FORMAT);
        $to = $to->setTimezone(new \DateTimeZone("UTC"))->format(TIME_FORMAT);
        return ["name" => $this->_fieldName, "operator" => \Component\Grid\Query\FilterItem::BETWEEN, "expression" => [$from, $to]];
    }
    private function _buildFromFilter(\DateTime $startTime)
    {
        $value = clone $startTime;
        $value = $value->setTimezone(new \DateTimeZone("UTC"))->format(TIME_FORMAT);
        return ["name" => $this->_fieldName, "operator" => \Component\Grid\Query\FilterItem::EQUALS_OR_GREATER_THAN, "expression" => $value];
    }
    private function _buildToFilter(\DateTime $endTime)
    {
        $value = $endTime->setTimezone(new \DateTimeZone("UTC"))->format(TIME_FORMAT);
        return ["name" => $this->_fieldName, "operator" => \Component\Grid\Query\FilterItem::EQUALS_OR_LESS_THAN, "expression" => $value];
    }
}

?>