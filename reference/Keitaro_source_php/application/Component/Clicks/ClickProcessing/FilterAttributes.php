<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class FilterAttributes implements StageInterface
{
    private $_includeAll = NULL;
    public function __construct($includeAll = false)
    {
        $this->_includeAll = $includeAll;
    }
    public function process($entries)
    {
        $fields = \Traffic\Model\Click::getFields();
        $hasColumn = true;
        if (!\Traffic\Repository\ParameterRepository::instance()->hasAffiliateNetworkId()) {
            $hasColumn = false;
        }
        $hasSubId = true;
        if (!\Traffic\Repository\ParameterRepository::instance()->hasSubId15()) {
            $hasSubId = false;
        }
        $hasXRequestedWith = true;
        if (!\Traffic\Repository\ParameterRepository::instance()->hasXRequestedWith()) {
            $hasXRequestedWith = false;
        }
        return array_map(function ($click) {
            $newClick = [];
            foreach ($fields as $fieldName => $type) {
                if (array_key_exists($fieldName, $click)) {
                    $newClick[$fieldName] = $click[$fieldName];
                } else {
                    if ($this->_includeAll) {
                        $newClick[$fieldName] = NULL;
                    }
                }
                if (array_key_exists($fieldName, $newClick)) {
                    if ($type == \Core\Type\Type::INTEGER) {
                        $newClick[$fieldName] = (int) $newClick[$fieldName];
                    }
                    if ($type == \Core\Type\Type::DECIMAL) {
                        $newClick[$fieldName] = (int) $newClick[$fieldName];
                    }
                }
            }
            if (!$hasColumn) {
                unset($newClick["affiliate_network_id"]);
            }
            if (!$hasSubId) {
                unset($newClick["sub_id_11"]);
                unset($newClick["sub_id_12"]);
                unset($newClick["sub_id_13"]);
                unset($newClick["sub_id_14"]);
                unset($newClick["sub_id_15"]);
            }
            if (!$hasXRequestedWith) {
                unset($click["x_requested_with_id"]);
            }
            return $newClick;
        }, $entries);
    }
}

?>