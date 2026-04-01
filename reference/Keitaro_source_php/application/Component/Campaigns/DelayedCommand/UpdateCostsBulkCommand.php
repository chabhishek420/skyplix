<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\DelayedCommand;

class UpdateCostsBulkCommand implements \Component\DelayedCommands\DelayedCommandInterface
{
    const NAME = "update_cost_bulk";
    public function priority()
    {
        return 5;
    }
    public function process($entries)
    {
        foreach ($entries as $entry) {
            \Traffic\Logging\Service\LoggerService::instance()->info("[UpdateCampaignCost] Updating cost: " . json_encode($entry));
            \Component\Campaigns\Service\CampaignService::instance()->updateCosts($this->_fromArrayToDTO($entry["data"]), $entry["currency"], $entry["timezone"], $entry["only_campaign_uniques"]);
        }
    }
    public static function enqueue($data, $currency, $timezone = "UTC", $onlyCampaignUniques = false)
    {
        $payload = ["data" => self::_fromDTOtoArray($data), "currency" => $currency, "timezone" => $timezone, "only_campaign_uniques" => $onlyCampaignUniques];
        $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => $payload, \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
    }
    public static function _fromDTOtoArray($data)
    {
        $result = [];
        foreach ($data as $datum) {
            $result[] = $datum->toArray();
        }
        return $result;
    }
    public function _fromArrayToDTO($data)
    {
        $result = [];
        foreach ($data as $datum) {
            $update = new \Component\Campaigns\UseCase\UpdateCampaignCostPayload();
            $update->setCampaigns($datum["_campaigns"]);
            $update->setCost($datum["_cost"]);
            $update->setStartDate($datum["_startDate"]);
            $update->setEndDate($datum["_endDate"]);
            $update->setFilters($datum["_filters"]);
            $result[] = $update;
        }
        return $result;
    }
}

?>