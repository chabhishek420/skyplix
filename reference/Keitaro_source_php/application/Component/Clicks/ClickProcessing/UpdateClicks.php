<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class UpdateClicks implements StageInterface
{
    public function process($entries)
    {
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
        \Core\Db\Db::instance()->beginTransaction();
        foreach ($entries as $click) {
            if (!$hasColumn) {
                unset($click["affiliate_network_id"]);
            }
            if (!$hasSubId) {
                unset($click["sub_id_11_id"]);
                unset($click["sub_id_12_id"]);
                unset($click["sub_id_13_id"]);
                unset($click["sub_id_14_id"]);
                unset($click["sub_id_15_id"]);
            }
            if (!$hasXRequestedWith) {
                unset($click["x_requested_with_id"]);
            }
            if (empty($click["click_id"])) {
                throw new StageError("[UpdateClicks] No click_id in command: " . json_encode($click));
            }
            \Core\Db\Db::instance()->update(\Traffic\Model\Click::getTableName(), "click_id = " . \Core\Db\Db::quote($click["click_id"]), $click);
        }
        \Core\Db\Db::instance()->commit();
    }
}

?>