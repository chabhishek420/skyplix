<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback\Stages;

class UpdateCostsStage implements \Component\Postback\ProcessPostback\StageInterface
{
    public function process(\Component\Postback\ProcessPostback\Payload $payload)
    {
        if ($payload->getPostback()->isIgnore()) {
            return NULL;
        }
        $this->_updateConversionCost($payload);
        $this->_updateClickCost($payload);
    }
    private function _updateConversionCost(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $postback = $payload->getPostback();
        $conversion = $payload->getConversionToSave();
        $conversion->setCost($postback->getCostCalculated());
    }
    private function _updateClickCost(\Component\Postback\ProcessPostback\Payload $payload)
    {
        $conversion = $payload->getConversionToSave();
        $click = $payload->getClick();
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($click->getCampaignId());
        if ($campaign->isCostRevShare() && $campaign->getCostValue()) {
            if ($campaign->isCostAuto()) {
                if ($payload->getPostback()->hasCost()) {
                    $cost = $payload->getPostback()->getCostCalculated();
                } else {
                    $cost = $click->getCost();
                }
            } else {
                $cost = $this->_getCostCalculated($campaign);
                if ($campaign->isCostPerThousand()) {
                    $cost = $cost / 1000;
                }
            }
            if (!$payload->isSale() && $campaign->isCostPerSale() || $payload->isRejected() && $campaign->isCostPerAcquisition()) {
                $cost = 0;
            }
            $click->set("cost", $cost);
            $conversion->setCost($cost);
        } else {
            $cost = $campaign->getCostValue() / 100 * $conversion->getRevenue();
            $click->set("cost", $cost);
            $conversion->setCost($cost);
        }
    }
    private function _getCostCalculated(\Traffic\Model\Campaign $campaign)
    {
        $currency = $campaign->getCostCurrency();
        $value = $campaign->getCostValue();
        if (!empty($currency) && $currency != \Traffic\Repository\CachedSettingsRepository::instance()->get("currency")) {
            $value = \Core\Currency\Service\CurrencyService::instance()->exchangeFromCurrencyToDefault($value, $currency);
        }
        return $value;
    }
}

?>