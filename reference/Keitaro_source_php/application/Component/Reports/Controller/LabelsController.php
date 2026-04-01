<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Controller;

class LabelsController extends \Admin\Controller\BaseController
{
    public function labelVariationsAction()
    {
        return \Component\Reports\Repository\LabelRepository::instance()->getLabelVariations();
    }
    public function refNameVariationsAction()
    {
        return \Component\Reports\Repository\LabelRepository::instance()->retRefNameVariations();
    }
    public function indexAction()
    {
        $campaignId = (int) $this->getParam("campaign_id");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
        $refName = $this->getParam("ref_name");
        $labelName = $this->getParam("label_name");
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        $labels = \Component\Reports\Repository\LabelRepository::instance()->labelsFor($campaign, $refName, $labelName);
        if (empty($labels)) {
            return NULL;
        }
        return (int) $labels;
    }
    public function updateAction()
    {
        $campaignId = (int) $this->getParam("campaign_id");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
        $refName = $this->getParam("ref_name");
        $items = $this->getParam("items");
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        \Component\Reports\Service\LabelService::instance()->updateLabels($campaign, $refName, $items);
        return ["success" => true];
    }
    public function replaceListAction()
    {
        $campaignId = (int) $this->getParam("campaign_id");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
        $refName = $this->getParam("ref_name");
        $refValues = $this->getParam("ref_values");
        $labelName = $this->getParam("label_name");
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        \Component\Reports\Service\LabelService::instance()->replaceList($campaign, $labelName, $refName, $refValues);
        return ["success" => true];
    }
}

?>