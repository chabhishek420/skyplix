<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\DelayedCommand;

class UpdateCostsCommand implements \Component\DelayedCommands\DelayedCommandInterface
{
    const NAME = "update_cost";
    public function priority()
    {
        return 5;
    }
    public function process($entries)
    {
        foreach ($entries as $entry) {
            \Traffic\Logging\Service\LoggerService::instance()->info("[UpdateCampaignCost] Updating cost: " . json_encode($entry));
            $payload = new \Component\Campaigns\UseCase\UpdateCampaignCostPayload();
            $payload->setCampaigns([$entry["campaign_id"]]);
            $payload->setFilters($entry["filters"]);
            $payload->setStartDate($entry["start_date"]);
            $payload->setEndDate($entry["end_date"]);
            $payload->setCost($entry["cost"]);
            \Component\Campaigns\Service\CampaignService::instance()->updateCosts([$payload], $entry["currency"], $entry["timezone"], $entry["only_campaign_uniques"]);
        }
    }
    public static function enqueue($campaignId, $startDate, $endDate, $timezone, $cost, $currency, $filters, $onlyCampaignUniques = false)
    {
        if (empty($campaignId)) {
            throw new \Core\Validator\ValidationError(["campaign_id" => "Is empty"]);
        }
        if (empty($startDate)) {
            throw new \Core\Validator\ValidationError(["start_date" => "Is empty"]);
        }
        if (empty($endDate)) {
            throw new \Core\Validator\ValidationError(["end_date" => "Is empty"]);
        }
        $payload = ["campaign_id" => $campaignId, "start_date" => $startDate, "end_date" => $endDate, "timezone" => $timezone, "cost" => $cost, "currency" => $currency, "filters" => $filters, "only_campaign_uniques" => $onlyCampaignUniques];
        $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => $payload, \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
    }
}

?>