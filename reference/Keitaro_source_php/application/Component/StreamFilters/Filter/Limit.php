<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Limit extends \Core\Filter\AbstractFilter
{
    public function getModes()
    {
        return NULL;
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $payload = $filter->getPayload();
        $limitExceeded = false;
        $stream = \Traffic\Repository\CachedStreamRepository::instance()->findCachedStream($filter->getStreamId());
        if (isset($payload["per_hour"]) && is_numeric($payload["per_hour"]) && $payload["per_hour"] <= \Traffic\HitLimit\Service\HitLimitService::instance()->perHour($stream, $rawClick->getDateTime())) {
            $limitExceeded = true;
        }
        if (!$limitExceeded && isset($payload["per_day"]) && is_numeric($payload["per_day"]) && $payload["per_day"] <= \Traffic\HitLimit\Service\HitLimitService::instance()->perDay($stream, $rawClick->getDateTime())) {
            $limitExceeded = true;
        }
        if (!$limitExceeded && isset($payload["total"]) && is_numeric($payload["total"]) && $payload["total"] <= \Traffic\HitLimit\Service\HitLimitService::instance()->total($stream)) {
            $limitExceeded = true;
        }
        if (!$limitExceeded) {
            $limitExceeded = $this->_checkNotSetValue($payload);
        }
        return !$limitExceeded;
    }
    private function _checkNotSetValue($payload)
    {
        $limitExceeded = false;
        if (array_key_exists("per_hour", $payload) && array_key_exists("per_day", $payload) && array_key_exists("total", $payload) && empty($payload["per_hour"]) && empty($payload["per_day"]) && empty($payload["total"])) {
            $limitExceeded = true;
        }
        return $limitExceeded;
    }
}

?>