<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Controller;

class CampaignsController extends \Admin\Controller\BaseController implements \Admin\Controller\EntityControllerInterface
{
    public function gridDefinitionAction()
    {
        $definition = new \Component\Campaigns\Grid\CampaignGridDefinition();
        return $definition->getGridDefinition();
    }
    public function listAsOptionsAction()
    {
        $addBlank = filter_var($this->getParam("add_blank"), FILTER_VALIDATE_BOOLEAN);
        $includeDisabled = $this->getParam("include_disabled");
        $includeDisabled = !is_null($includeDisabled) ? filter_var($includeDisabled, FILTER_VALIDATE_BOOLEAN) : true;
        $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allActiveForList($includeDisabled);
        $campaigns = \Component\Users\Service\AclService::instance()->filterByAcl($campaigns, false, $this->getUser());
        return \Component\Campaigns\Repository\CampaignRepository::instance()->listAsOptions($campaigns, $this->getParam("key"), $addBlank);
    }
    public function indexAction()
    {
        if ($this->getParam("active")) {
            $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allActive(NULL, "position, id");
        } else {
            $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allNotDeleted();
        }
        $campaigns = \Component\Users\Service\AclService::instance()->filterByAcl($campaigns, false, $this->getUser());
        return $this->serialize($campaigns, new \Component\Campaigns\Serializer\CampaignSerializer($this->getParam("extended")));
    }
    public function withStatsAction()
    {
        if (@file_get_contents(ROOT . "/var/license/key.lic") === "1111-1111-1111-1111" || substr_count(@file_get_contents(ROOT . "/application/Core/Application/TsService.php"), "return true", 0) === 4) {
            return [];
        }
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\Campaigns\Repository\CampaignRepository::instance()->allWithStats($this->getPostParams(), $userParams);
    }
    public function deletedAction()
    {
        $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allDeleted();
        $campaigns = \Component\Users\Service\AclService::instance()->filterByAcl($campaigns, false, $this->getUser());
        return $this->serialize($campaigns, new \Component\Campaigns\Serializer\CampaignSerializer());
    }
    public function showAction()
    {
        $id = (int) $this->getParam("id");
        $withStreams = $this->getParam("withStreams");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($id);
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        if ($campaign->isDeleted()) {
            $this->throwNotFound();
        }
        if (!\Component\Users\Service\AclService::instance()->isResourceAllowed($this->getUser(), "streams")) {
            $withStreams = false;
        }
        return $this->serialize($campaign, new \Component\Campaigns\Serializer\CampaignSerializer(true, $withStreams));
    }
    public function restoreAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allByIds($ids);
        if ($this->isTrial() && !\Component\Campaigns\Repository\CampaignRepository::instance()->checkTrialCampaign(count($campaigns))) {
            $this->throwDenyBecauseTrial();
        }
        $campaigns = \Component\Users\Service\AclService::instance()->filterByAcl($campaigns, true, $this->getUser());
        foreach ($campaigns as $campaign) {
            \Component\Campaigns\Service\CampaignService::instance()->makeActive($campaign);
        }
        return $this->serialize($campaigns, new \Component\Campaigns\Serializer\CampaignSerializer(true));
    }
    public function createAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Campaign::aclKey())) {
            $this->throwDeny();
        }
        if ($this->isTrial() && !\Component\Campaigns\Repository\CampaignRepository::instance()->checkTrialCampaign(1)) {
            $this->throwDenyBecauseTrial();
        }
        if ($this->isPost()) {
            $campaign = \Component\Campaigns\Service\CampaignService::instance()->create($this->getPostParams());
            \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), [$campaign], false);
            $allParams = $this->getPostParams();
            if ($this->isTrial() && !empty($allParams["streams"]) && \Component\Streams\Repository\StreamRepository::instance()->getMaxTrialStreams() < count($allParams["streams"])) {
                $this->throwDenyBecauseTrial();
            }
            if (isset($allParams["streams"])) {
                \Component\Streams\Service\StreamService::instance()->updateStreams($campaign, $allParams["streams"]);
            }
            return $this->serialize($campaign->reload(), new \Component\Campaigns\Serializer\CampaignSerializer());
        }
        return NULL;
    }
    public function updateAction()
    {
        $id = (int) $this->getParam("id");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($id);
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        if ($this->isPost()) {
            $campaign = \Component\Campaigns\Service\CampaignService::instance()->updateCampaign($campaign, $this->getPostParams());
            $allParams = $this->getPostParams();
            if ($this->isTrial() && !empty($allParams["streams"]) && \Component\Streams\Repository\StreamRepository::instance()->getMaxTrialStreams() < count($allParams["streams"])) {
                $this->throwDenyBecauseTrial();
            }
            if (isset($allParams["streams"])) {
                \Component\Streams\Service\StreamService::instance()->updateStreams($campaign, $allParams["streams"]);
                \Component\Campaigns\Service\CampaignService::instance()->resortStreams($campaign);
            }
            $withStreams = true;
            if (!\Component\Users\Service\AclService::instance()->isResourceAllowed($this->getUser(), "streams")) {
                $withStreams = false;
            }
            return $this->serialize($campaign, new \Component\Campaigns\Serializer\CampaignSerializer(true, $withStreams));
        }
        return NULL;
    }
    public function cloneAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Campaign::aclKey())) {
            $this->throwDeny();
        }
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $oldCampaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allByIds($ids);
        $oldCampaigns = \Component\Users\Service\AclService::instance()->filterByAcl($oldCampaigns, true, $this->getUser());
        if ($this->isTrial() && !\Component\Campaigns\Repository\CampaignRepository::instance()->checkTrialCampaign(count($oldCampaigns))) {
            $this->throwDenyBecauseTrial();
        }
        $campaigns = [];
        foreach ($oldCampaigns as $oldCampaign) {
            $campaigns[] = \Component\Campaigns\Service\CampaignService::instance()->cloneCampaign($oldCampaign);
        }
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), $campaigns, false);
        return $this->serialize($campaigns, new \Component\Campaigns\Serializer\CampaignSerializer(true));
    }
    public function enableAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $sourceCampaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allByIds($ids);
        $sourceCampaigns = \Component\Users\Service\AclService::instance()->filterByAcl($sourceCampaigns, true, $this->getUser());
        $campaigns = [];
        foreach ($sourceCampaigns as $sourceCampaign) {
            $campaigns[] = \Component\Campaigns\Service\CampaignService::instance()->enableCampaign($sourceCampaign);
        }
        return $this->serialize($campaigns, new \Component\Campaigns\Serializer\CampaignSerializer(true));
    }
    public function disableAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $sourceCampaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allByIds($ids);
        $sourceCampaigns = \Component\Users\Service\AclService::instance()->filterByAcl($sourceCampaigns, true, $this->getUser());
        $campaigns = [];
        foreach ($sourceCampaigns as $sourceCampaign) {
            $campaigns[] = \Component\Campaigns\Service\CampaignService::instance()->disableCampaign($sourceCampaign);
        }
        return $this->serialize($campaigns, new \Component\Campaigns\Serializer\CampaignSerializer($this->getParam("extended")));
    }
    public function archiveAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $sourceCampaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allByIds($ids);
        $sourceCampaigns = \Component\Users\Service\AclService::instance()->filterByAcl($sourceCampaigns, true, $this->getUser());
        foreach ($sourceCampaigns as $sourceCampaign) {
            \Component\Campaigns\Service\CampaignService::instance()->archive($sourceCampaign);
        }
        return $this->serialize($sourceCampaigns, new \Component\Campaigns\Serializer\CampaignSerializer($this->getParam("extended")));
    }
    public function savePositionsAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allByIds($ids);
        $campaigns = \Component\Users\Service\AclService::instance()->filterByAcl($campaigns, true, $this->getUser());
        if (empty($campaigns)) {
            return NULL;
        }
        $dict = [];
        foreach ($campaigns as $campaign) {
            $dict[$campaign->getId()] = $campaign;
        }
        $campaigns = [];
        foreach ($ids as $id) {
            $campaigns[] = $dict[$id];
        }
        \Component\Campaigns\Service\CampaignService::instance()->updatePositions($this->getParam("group_id"), $campaigns);
    }
    public function updateCostsAction()
    {
        $id = (int) $this->getParam("id");
        $onlyCampaignUniques = $this->getParam("only_campaign_uniques");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($id);
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        $payload = new \Component\Campaigns\UseCase\UpdateCampaignCostPayload();
        $payload->setCampaigns([$id]);
        $payload->setStartDate($this->getPostParam("start_date"));
        $payload->setEndDate($this->getPostParam("end_date"));
        $payload->setCost($this->getPostParam("cost"));
        $payload->setFilters($this->getPostParam("filters"));
        \Component\Campaigns\DelayedCommand\UpdateCostsBulkCommand::enqueue([$payload], $this->getPostParam("currency"), $this->getPostParam("timezone"), $onlyCampaignUniques);
        return ["success" => true];
    }
    public function costTypesAction()
    {
        return \Component\Campaigns\Repository\CampaignRepository::instance()->getCostTypes();
    }
    public function tokenAction()
    {
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($this->getParam("id"));
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        return ["token" => $campaign->getToken()];
    }
    public function getBindVisitorTypesAction()
    {
        return \Component\Campaigns\Repository\CampaignRepository::instance()->getBindVisitorTypes();
    }
    public function saveNoteAction()
    {
        $id = $this->getParam("id");
        $note = $this->getParam("note");
        $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->allByIds([$id]);
        $campaigns = \Component\Users\Service\AclService::instance()->filterByAcl($campaigns, true, $this->getUser());
        $items = [];
        foreach ($campaigns as $campaign) {
            $items[] = \Component\Campaigns\Service\CampaignService::instance()->updateNote($campaign, $note);
        }
        return $this->serialize($items, new \Component\Campaigns\Serializer\CampaignSerializer());
    }
    public function updateStreamsAction()
    {
        $id = $this->getParam("campaign_id");
        $streams = $this->getParam("streams");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($id);
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        $newStreams = \Component\Streams\Service\StreamService::instance()->updateStreams($campaign, $streams);
        return $this->serialize($newStreams, new \Component\Streams\Serializer\StreamSerializer());
    }
    public function cleanArchiveAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Campaign::aclKey())) {
            $this->throwDeny();
        }
        $pruner = new \Component\Campaigns\PruneTask\PruneCampaigns();
        $pruner->deleteAll();
    }
}

?>