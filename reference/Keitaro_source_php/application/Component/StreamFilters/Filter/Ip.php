<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Ip extends \Core\Filter\AbstractFilter
{
    const COLLAPSE_AFTER_CNT = 10;
    public function getGroup()
    {
        return "filters.groups.geo";
    }
    public function getTooltip()
    {
        return \Core\Locale\LocaleService::t("filters.ip_comment");
    }
    public function decorate(\Traffic\Model\StreamFilter $filter, \Traffic\Model\TrafficSource $ts = NULL)
    {
        $label = \Core\Locale\LocaleService::t("filters.names.ip");
        return $this->_collapsible($label, $filter->getMode(), $filter->getPayload(), COLLAPSE_AFTER_CNT);
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $found = false;
        $ip = $rawClick->getIpString();
        $payload = $filter->getPayload();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach ($payload as $string) {
            $tok = strtok($string, ",;");
            while ($tok !== false) {
                if ($this->_checkIp($tok, $ip)) {
                    $found = true;
                } else {
                    $tok = strtok(",;");
                }
            }
        }
        return $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && $found || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && !$found;
    }
    private function _checkIp($mask, $ip)
    {
        if (strstr($mask, "/")) {
            return \Traffic\Tools\Tools::ipInCIDR($ip, $mask);
        }
        if (substr_count($mask, ".") == 6) {
            return \Traffic\Tools\Tools::ipInInterval($ip, $mask);
        }
        return \Traffic\Tools\Tools::ipInMask($ip, $mask);
    }
    public function getTemplate()
    {
        $str = "<stream-multi-value-input ng-model=\"filter.payload\" separators=\"[',',';']\" field-name=\"'ip'\"></stream-multi-value-input>";
        return $str;
    }
}

?>