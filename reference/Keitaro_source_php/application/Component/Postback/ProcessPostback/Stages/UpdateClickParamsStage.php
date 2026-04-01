<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback\Stages;

class UpdateClickParamsStage implements \Component\Postback\ProcessPostback\StageInterface
{
    public function process(\Component\Postback\ProcessPostback\Payload $payload)
    {
        if ($payload->getPostback()->isIgnore()) {
            return NULL;
        }
        $this->_updateClickLandingParams($payload);
        $this->_updateClickSubIdExtraParams($payload);
    }
    private function _updateClickLandingParams(\Component\Postback\ProcessPostback\Payload $payload)
    {
        if ($payload->isNewConversion()) {
            $click = $payload->getClick();
            $postback = $payload->getPostback();
            if ($click->getLandingId()) {
                if (!$click->getLandingClicked()) {
                    $click->set("landing_clicked", true);
                }
                if (!$click->getLandingClickedDatetime()) {
                    $click->set("landing_clicked_datetime", $postback->getDateTime());
                }
            }
        }
    }
    private function _updateClickSubIdExtraParams(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $click = $payload->getClick();
        $postback = $payload->getPostback();
        $conversion = $payload->getConversionToSave();
        $fields = $this->_getParamFields();
        foreach ($fields as $field) {
            if ($conversion->get($field)) {
                $click->set($field, $conversion->get($field));
            }
        }
        if (!$conversion->getCampaignId()) {
            throw new \Component\Postback\PostbackError("Campaign id is empty for conversion " . $conversion->getId());
        }
    }
    private function _getParamFields()
    {
        $params = [];
        for ($n = 1; $n <= \Traffic\Model\Click::getSubIdCount(); $n++) {
            $params[] = "sub_id_" . $n . "_id";
        }
        for ($n = 1; $n <= \Traffic\Model\Click::EXTRA_PARAM_COUNT; $n++) {
            $params[] = "extra_param_" . $n;
        }
        return $params;
    }
}

?>