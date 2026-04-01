<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback\Stages;

class UpdateConversionParamsStage implements \Component\Postback\ProcessPostback\StageInterface
{
    public function process(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $payload->haltOnOfferDisallowRebill();
        if ($payload->getPostback()->isIgnore()) {
            $this->_processIgnorePostback($payload);
        } else {
            $this->_updateParams($payload);
            $this->_updateDatetime($payload);
            $this->_updateSubIds($payload);
            $this->_updateExtraParam($payload);
        }
    }
    private function _processIgnorePostback(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $saveConversion = $payload->getConversionToSave();
        $originalStatus = $payload->getPostback()->getOriginalStatus();
        if ($saveConversion) {
            $saveConversion->set("original_status", $originalStatus)->save();
        }
        \Core\Logging\Service\PostbackLoggerService::instance()->log("Postback ignored because  '" . $originalStatus . "' status  received");
    }
    private function _updateParams(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $postback = $payload->getPostback();
        $conversion = $payload->getConversionToSave();
        $conversion->set("params", $postback->getParams());
    }
    private function _updateDatetime(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $postback = $payload->getPostback();
        $conversion = $payload->getConversionToSave();
        $click = $payload->getClick();
        if (!$payload->isStatusChange()) {
            $conversion->set("postback_datetime", $postback->getDateTime());
        }
        $oldConversionSaleDateTime = $conversion->getSaleDateTime();
        if (($conversion->isSale() || $conversion->isRebill()) && empty($oldConversionSaleDateTime)) {
            $conversion->set("sale_datetime", $postback->getDatetime());
        }
        if (!$conversion->isSale() && !$conversion->isRebill()) {
            $conversion->set("sale_datetime", NULL);
        }
        if ($click->getDateTime() != $conversion->getClickDateTime()) {
            $conversion->set("click_datetime", $click->getDateTime());
        }
    }
    private function _updateSubIds(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $postback = $payload->getPostback();
        $conversion = $payload->getConversionToSave();
        foreach ($postback->getSubIds() as $name => $value) {
            $ref = \Component\Clicks\Service\SubIdNService::instance()->getOrCreateSubIdN($value);
            $conversion->set($name . "_id", $ref->getId());
        }
    }
    private function _updateExtraParam(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $postback = $payload->getPostback();
        $conversion = $payload->getConversionToSave();
        foreach ($postback->getExtraParams() as $name => $value) {
            $conversion->set($name, $value);
        }
    }
}

?>