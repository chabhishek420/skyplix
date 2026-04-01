<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Ipv6 extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.geo";
    }
    protected function _isIpV6($ip)
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) || empty($ip);
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $ip = $rawClick->getIpString();
        return $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && $this->_isIpV6($ip) || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && !$this->_isIpV6($ip);
    }
    public function getTemplate()
    {
        return "";
    }
}

?>