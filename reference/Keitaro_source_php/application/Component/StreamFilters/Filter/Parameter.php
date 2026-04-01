<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Parameter extends \Core\Filter\AbstractFilter
{
    public function getGroup()
    {
        return "filters.groups.parameters";
    }
    public function isSingle()
    {
        return false;
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $found = false;
        $data = $filter->getPayload();
        $value = $this->getParam($data["name"]);
        if (is_array($data["value"])) {
            foreach ($data["value"] as $row) {
                if (\Component\StreamFilters\Service\StreamFilterService::instance()->findInWithRegexSupport($value, $row)) {
                    $found = true;
                }
            }
        }
        return !($filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT && !$found || $filter->getMode() == \Traffic\Model\StreamFilter::REJECT && $found);
    }
    public function getParam($key)
    {
        $param = $this->getServerRequest()->getParam($key);
        if (!is_null($param)) {
            return $param;
        }
        $postdata = file_get_contents("php://input");
        if (!empty($postdata)) {
            $params = json_decode($postdata, true);
            if (isset($params[$key])) {
                return $params[$key];
            }
        }
    }
}

?>