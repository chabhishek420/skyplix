<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Controller;

class ClicksController extends \Admin\Controller\BaseController
{
    protected $campaign = NULL;
    public function logDefinitionAction()
    {
        $definition = new \Component\Clicks\Grid\ClickLogDefinition();
        return $definition->getGridDefinition();
    }
    public function logAction()
    {
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\Clicks\Repository\ClickRepository::instance()->log($this->getPostParams(), $userParams);
    }
    public function dictionaryAction()
    {
        $name = $this->getParam("name");
        $dict = new \Component\Grid\Dictionary($this->getUser());
        return $dict->get($name);
    }
    public function updateCostsAction()
    {
        $onlyCampaignUniques = $this->getParam("only_campaign_uniques");
        $campaignIds = $this->getPostParam("campaign_ids");
        foreach ($campaignIds as $campaignId) {
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
            if (!$this->isEditAllowed($campaign)) {
                $this->throwDeny();
            }
        }
        $costs = $this->getPostParam("costs");
        if (empty($costs)) {
            $this->throwError("Costs empty");
        }
        $data = [];
        foreach ($costs as $cost) {
            $payload = new \Component\Campaigns\UseCase\UpdateCampaignCostPayload();
            $payload->setCampaigns($campaignIds);
            $payload->setStartDate($cost["start_date"]);
            $payload->setEndDate($cost["end_date"]);
            $payload->setCost($cost["cost"]);
            $payload->setFilters($cost["filters"]);
            $data[] = $payload;
        }
        \Component\Campaigns\DelayedCommand\UpdateCostsBulkCommand::enqueue($data, $this->getPostParam("currency"), $this->getPostParam("timezone"), $onlyCampaignUniques);
        return ["success" => true];
    }
}

?>