<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Isp extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.geo";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $found = false;
        $clickISP = $rawClick->getIsp();
        $payload = $filter->getPayload();
        if (!is_array($payload)) {
            $payload = [];
        }
        foreach ($payload as $filterISP) {
            if (\Component\StreamFilters\Service\StreamFilterService::instance()->findInWithRegexSupport($clickISP, $filterISP, false)) {
                $found = true;
                return $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && $found || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && !$found;
            }
        }
    }
    public function getTemplate()
    {
        if (\Traffic\GeoDb\Repository\GeoDbRepository::instance()->isAvailable(\Component\GeoDb\Maxmind\MaxmindIsp::ID) || \Traffic\GeoDb\Repository\GeoDbRepository::instance()->isAvailable(\Component\GeoDb\Ip2Location\Ip2LocationDb4::ID) || \Traffic\GeoDb\Repository\GeoDbRepository::instance()->isAvailable(\Component\GeoDb\ProIP\ProIPDBEssential::ID)) {
            return self::getTemplate();
        }
        return \Core\Locale\LocaleService::t("filters.no_isp_db");
    }
}

?>