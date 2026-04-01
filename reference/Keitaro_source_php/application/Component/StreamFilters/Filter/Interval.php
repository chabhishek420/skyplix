<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Interval extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.time";
    }
    public function getModes()
    {
        return [\Traffic\Model\StreamFilter::ACCEPT => \Core\Locale\LocaleService::t("filters.interval_options." . \Traffic\Model\StreamFilter::ACCEPT), \Traffic\Model\StreamFilter::REJECT => \Core\Locale\LocaleService::t("filters.interval_options." . \Traffic\Model\StreamFilter::REJECT)];
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $payload = $filter->getPayload();
        $currentTime = $rawClick->getDateTime();
        $tz = NULL;
        if (!empty($payload["timezone"])) {
            $tz = new \DateTimeZone($payload["timezone"]);
        }
        $include = true;
        if (!empty($payload["from"])) {
            $tmp = explode("T", $payload["from"]);
            $payload["from"] = $tmp[0];
            $from = new \DateTime($payload["from"], $tz);
            $from->setTime(0, 0, 0);
            $include = $from <= $currentTime;
        }
        if (!empty($payload["to"]) && $include) {
            $tmp = explode("T", $payload["to"]);
            $payload["to"] = $tmp[0];
            $to = new \DateTime($payload["to"], $tz);
            $to->setTime(0, 0, 0);
            $include = $currentTime <= $to;
        }
        return $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && $include || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && !$include;
    }
}

?>