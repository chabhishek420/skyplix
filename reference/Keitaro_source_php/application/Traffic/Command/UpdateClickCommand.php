<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Command\DelayedCommand;

class UpdateClickCommand implements \Component\DelayedCommands\DelayedCommandInterface
{
    protected $_pipeline = NULL;
    const NAME = "update_click";
    public function __construct()
    {
        $this->_pipeline = [new \Component\Clicks\ClickProcessing\LoadClicks(), new \Component\Clicks\ClickProcessing\ExtractReferences(true), new \Component\Clicks\ClickProcessing\FilterAttributes(false), new \Component\Clicks\ClickProcessing\UpdateClicks()];
    }
    public function priority()
    {
        return 2;
    }
    public function process($entries)
    {
        $count = count($entries);
        foreach ($this->_pipeline as $stage) {
            $stageName = \Traffic\Tools\Tools::demodulize(get_class($stage));
            \Traffic\Logging\Service\LoggerService::instance()->debug(\Traffic\Profiler\ProfilerService::instance()->step($stageName . " start (" . $count . " items)"));
            $entries = $stage->process($entries);
            \Traffic\Logging\Service\LoggerService::instance()->debug(\Traffic\Profiler\ProfilerService::instance()->step($stageName . " end"));
        }
        unset($entries);
    }
    public static function saveLpClick($subId, $offerId, $params = NULL, $landingId = NULL)
    {
        $payload = [];
        $payload["sub_id"] = $subId;
        $payload["offer_id"] = $offerId;
        $offer = \Traffic\Repository\CachedOfferRepository::instance()->findCached($offerId);
        if (empty($offer)) {
            \Traffic\Logging\Service\LoggerService::instance()->info("UpdateLpClick: Offer #" . $offerId . " is not found. Request sent from landing: " . $landingId . ". Skip updating.");
        } else {
            $payload["affiliate_network_id"] = $offer->getAffiliateNetworkId();
            if ($landingId) {
                $datetime = (new \Datetime())->format(\Core\Model\AbstractModel::DATETIME_FORMAT);
                $payload["landing_clicked"] = 1;
                $payload["landing_clicked_datetime"] = $datetime;
            }
            if ($offer->isCPC() && !$offer->isPayoutAuto()) {
                $payload["is_sale"] = 1;
                $revenue = \Core\Currency\Service\CurrencyService::instance()->exchange($offer->getPayoutValue(), $offer->getPayoutCurrency(), \Traffic\Repository\CachedSettingsRepository::instance()->get("currency"));
                $payload["revenue"] = $revenue;
            }
            for ($i = 1; $i <= \Traffic\Model\Click::getSubIdCount(); $i++) {
                $subIdParam = "sub_id_" . $i;
                if (!empty($params[$subIdParam])) {
                    $payload[$subIdParam] = urldecode($params[$subIdParam]);
                }
            }
            for ($i = 1; $i <= \Traffic\Model\Click::EXTRA_PARAM_COUNT; $i++) {
                if (!empty($params["extra_param_" . $i])) {
                    $payload["extra_param_" . $i] = urldecode($params["extra_param_" . $i]);
                }
            }
            if (!empty($params["is_bot"])) {
                $payload["is_bot"] = (int) $params["is_bot"];
            }
            \Traffic\CommandQueue\Service\DelayedCommandService::instance()->initRetry($payload, NAME);
            $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => $payload, \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
            \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
            return $command;
        }
    }
    public static function updateTokens($subId, $params)
    {
        $payload = [];
        $payload["sub_id"] = $subId;
        for ($i = 1; $i <= \Traffic\Model\Click::getSubIdCount(); $i++) {
            $subIdParam = "sub_id_" . $i;
            if (!empty($params[$subIdParam])) {
                $payload[$subIdParam] = urldecode($params[$subIdParam]);
            }
        }
        for ($i = 1; $i <= \Traffic\Model\Click::EXTRA_PARAM_COUNT; $i++) {
            if (!empty($params["extra_param_" . $i])) {
                $payload["extra_param_" . $i] = urldecode($params["extra_param_" . $i]);
            }
        }
        if (!empty($params["offer_id"])) {
            $payload["offer_id"] = $params["offer_id"];
            $offer = \Traffic\Repository\CachedOfferRepository::instance()->findCached($payload["offer_id"]);
            $payload["affiliate_network_id"] = $offer->getAffiliateNetworkId();
        }
        if (!empty($params["is_bot"])) {
            $payload["is_bot"] = (int) $params["is_bot"];
        }
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->initRetry($payload, NAME);
        $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => $payload, \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
        return $command;
    }
}

?>