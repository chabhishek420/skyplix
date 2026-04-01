<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback\Stages;

class UpdateRevenueStage implements \Component\Postback\ProcessPostback\StageInterface
{
    public function process(\Component\Postback\ProcessPostback\Payload $payload)
    {
        if ($payload->getPostback()->isIgnore()) {
            return NULL;
        }
        $this->_updateConversionRevenue($payload);
        $this->_updateClickRevenue($payload);
    }
    private function _updateConversionRevenue(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $postback = $payload->getPostback();
        $offer = $payload->getOffer();
        $conversion = $payload->getConversionToSave();
        $revenue = $postback->getRevenueCalculated($offer);
        if (!is_null($revenue)) {
            $conversion->setRevenue($revenue);
        }
    }
    private function _updateClickRevenue(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $click = $payload->getClick();
        $click->set("lead_revenue", $payload->getTotalLeadRevenue());
        $click->set("sale_revenue", $payload->getTotalSaleRevenue());
        $click->set("rejected_revenue", $payload->getTotalRejectedRevenue());
    }
}

?>