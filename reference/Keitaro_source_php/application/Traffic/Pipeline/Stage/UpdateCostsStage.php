<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class UpdateCostsStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $campaign = $payload->getCampaign();
        $rawClick = $payload->getRawClick();
        $request = $payload->getServerRequest();
        if (empty($campaign)) {
            throw new StageException("No campaign");
        }
        if (empty($rawClick)) {
            throw new StageException("No rawClick");
        }
        if ($campaign->isCostAuto()) {
            $cost = str_replace(",", ".", $rawClick->get("cost"));
            $cost = $this->_patchMegapush($cost);
            $currency = $rawClick->get("currency");
        } else {
            $cost = $campaign->getCostValue();
            $currency = $campaign->getCostCurrency();
        }
        $rawClick->setCost(0);
        if (!empty($cost) && !is_numeric($cost)) {
            $logEntry->add("Incorrect cost received - " . $cost);
            return $payload;
        }
        $cost = $this->_applyTrafficLoss($cost, $campaign->getTrafficLoss());
        if ($campaign->isCostPerAcquisition() || $campaign->isCostPerSale() || $campaign->isCostRevShare()) {
            if ($campaign->isCostPerUnique()) {
                if ($campaign->isCostPerThousand() && $cost) {
                    if (!($campaign->isCostPerClick() && $cost)) {
                        $rawClick->setCost($cost, $currency);
                    }
                } else {
                    $rawClick->setCost($cost / 1000, $currency);
                }
                return $payload;
            }
            if ($rawClick->isUniqueCampaign()) {
                $rawClick->setCost($cost, $currency);
            }
            return $payload;
        }
        return $payload;
    }
    private function _applyTrafficLoss($cost, $trafficLossValue)
    {
        if ($cost && $trafficLossValue) {
            return $cost / (1 - $trafficLossValue / 100);
        }
        return $cost;
    }
    private function _patchMegapush($cost)
    {
        if (is_string($cost) && preg_match("/^00[0-9]+/", $cost)) {
            $cost = preg_replace("/^00/", "0.", $cost);
        }
        return $cost;
    }
}

?>