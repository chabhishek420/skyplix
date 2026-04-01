<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Browser extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $browser = $rawClick->getBrowser();
        $found = false;
        $payload = $filter->getPayload();
        if (!empty($browser)) {
            $browser .= " " . $rawClick->getBrowserVersion();
        }
        if (!is_array($payload)) {
            $payload = explode(",", $payload);
        }
        foreach ($payload as $row) {
            $row = str_replace("Google Chrome", "Chrome", $row);
            if (\Component\StreamFilters\Service\StreamFilterService::instance()->findInWithRegexSupport($browser, $row, false)) {
                $found = true;
                return !(!$found && $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT || $found && $filter->getMode() == \Traffic\Model\StreamFilter::REJECT);
            }
        }
    }
}

?>