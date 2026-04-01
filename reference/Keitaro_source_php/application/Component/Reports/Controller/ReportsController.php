<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Controller;

class ReportsController extends \Admin\Controller\BaseController
{
    public function definitionAction()
    {
        $definition = new \Component\Reports\Grid\ReportDefinition();
        return $definition->getGridDefinition();
    }
    public function buildAction()
    {
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\Reports\Repository\ReportRepository::instance()->get($this->getPostParams(), $userParams);
    }
    public function summaryAction()
    {
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\Clicks\Repository\ClickRepository::instance()->summary($this->getPostParams(), $userParams);
    }
    public function columnsAsOptionsAction()
    {
        $definition = new \Component\Reports\Grid\ReportDefinition();
        return $definition->listAsOptions();
    }
    public function parameterAliasesAction()
    {
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($this->getParam("campaign_id"));
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        return \Component\Campaigns\Repository\CampaignRepository::instance()->getParameterAliases($campaign);
    }
    public function statsForCampaignAction()
    {
        $campaignId = (int) $this->getParam("campaign_id");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\Reports\Repository\ReportRepository::instance()->briefCampaignStats($campaign, $userParams, $this->getParam("range"));
    }
}

?>