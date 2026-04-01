<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback\Stages;

class UpdateStatusesStage implements \Component\Postback\ProcessPostback\StageInterface
{
    public function process(\Component\Postback\ProcessPostback\Payload $payload)
    {
        if ($payload->getPostback()->isIgnore()) {
            return NULL;
        }
        $this->_updateConversionStatuses($payload);
        $this->_updateClickStatuses($payload);
    }
    private function _updateConversionStatuses(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $postback = $payload->getPostback();
        $newConversion = $payload->getNewConversion();
        $saveConversion = $payload->getConversionToSave();
        if (!($saveConversion->isRebill() && $postback->isSale())) {
            if ($payload->isStatusChange()) {
                $saveConversion->set("previous_status", $saveConversion->getStatus());
            }
            $saveConversion->set("status", $postback->getStatus());
        }
        if ($payload->isAdditionalConversion() && $payload->checkOldConversionsHasSale() && $newConversion->isSale()) {
            $newConversion->set("status", \Traffic\Model\Conversion::REBILL);
        }
        $saveConversion->set("original_status", $postback->getOriginalStatus());
        $conversionStatus = $saveConversion->getStatus();
        if (empty($conversionStatus)) {
            $saveConversion->set("status", \Traffic\Model\Conversion::LEAD);
        }
    }
    private function _updateClickStatuses(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $conversion = $payload->getConversionToSave();
        $click = $payload->getClick();
        if ($conversion->isLead()) {
            $click->set("is_lead", 1);
            $click->set("is_sale", 0);
            $click->set("is_rejected", 0);
        }
        if ($conversion->isRebill()) {
            $click->set("is_lead", 0);
            $click->set("is_sale", 1);
            $click->set("is_rejected", 0);
            $click->set("rebills", $click->getRebills() + 1);
        }
        if ($conversion->isSale()) {
            $click->set("is_lead", 0);
            $click->set("is_sale", 1);
            $click->set("is_rejected", 0);
        }
        if ($conversion->isRejected()) {
            $click->set("is_lead", 0);
            $click->set("is_sale", 0);
            $click->set("is_rejected", 1);
        }
    }
}

?>