<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Controller;

class StreamsController extends \Admin\Controller\BaseController implements \Admin\Controller\EntityControllerInterface
{
    public function listAsOptionsAction()
    {
        $exclude = $this->getParam("exclude");
        $ids = \Component\Users\Service\AclService::instance()->getAllowedCampaignIds($this->getUser());
        if ($ids == \Component\Users\Service\AclService::ALLOW_NONE) {
            return [];
        }
        if ($ids == \Component\Users\Service\AclService::ALLOW_ANY) {
            $ids = NULL;
        }
        $streams = \Component\Streams\Repository\StreamRepository::instance()->getActiveCampaignStreams($exclude, $ids);
        return \Component\Streams\Repository\StreamRepository::instance()->getStreamsAsOptions($streams);
    }
    public function indexAction()
    {
        $id = intval($this->getParam("campaign_id"));
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($id);
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        $streams = \Component\Streams\Repository\StreamRepository::instance()->allOrderedStreamsForCampaign($campaign);
        return $this->serialize($streams, new \Component\Streams\Serializer\StreamSerializer());
    }
    public function deletedAction()
    {
        $ids = \Component\Users\Service\AclService::instance()->getAllowedCampaignIds($this->getUser());
        if ($ids == \Component\Users\Service\AclService::ALLOW_NONE) {
            return [];
        }
        if ($ids == \Component\Users\Service\AclService::ALLOW_ANY) {
            $ids = NULL;
        }
        $streams = \Component\Streams\Repository\StreamRepository::instance()->allDeleted(["allowedCampaignIds" => $ids]);
        return $this->serialize($streams, new \Component\Streams\Serializer\StreamSearchResultSerializer());
    }
    public function showAction()
    {
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($this->getParam("id"));
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        return $this->serialize($stream, new \Component\Streams\Serializer\StreamSerializer());
    }
    public function restoreAction()
    {
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($this->getParam("id"));
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        if ($this->isTrial() && !\Component\Streams\Repository\StreamRepository::instance()->checkTrialStream($stream->getCampaignId())) {
            $this->throwDenyBecauseTrial();
        }
        \Component\Streams\Service\StreamService::instance()->restoreStream($stream);
        return $this->serialize($stream, new \Component\Streams\Serializer\StreamSerializer());
    }
    public function createAction()
    {
        $campaignId = (int) $this->getPostParam("campaign_id");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        if ($this->isTrial() && (!\Component\Streams\Service\StreamService::instance()->checkTrialStreamFilters($this->getPostParams()) || !\Component\Streams\Repository\StreamRepository::instance()->checkTrialStream($campaignId))) {
            $this->throwDenyBecauseTrial();
        }
        if ($this->isPost()) {
            $data = $this->getPostParams();
            $data["campaign_id"] = $campaign->getId();
            $stream = \Component\Streams\Service\StreamService::instance()->create($data);
            return $this->serialize($stream, new \Component\Streams\Serializer\StreamSerializer());
        }
    }
    public function updateAction()
    {
        $id = intval($this->getParam("id"));
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($id);
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        if ($this->isTrial() && !\Component\Streams\Service\StreamService::instance()->checkTrialStreamFilters($this->getPostParams())) {
            $this->throwDenyBecauseTrial();
        }
        if ($this->isPost()) {
            \Component\Streams\Service\StreamService::instance()->update($stream, $this->getPostParams());
            return $this->serialize($stream, new \Component\Streams\Serializer\StreamSerializer(true));
        }
    }
    public function deleteAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        foreach ($ids as $id) {
            $stream = \Component\Streams\Repository\StreamRepository::instance()->find($id);
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
            if (!$this->isEditAllowed($campaign)) {
                $this->throwDeny();
            }
            \Component\Streams\Service\StreamService::instance()->archiveStream($stream);
        }
    }
    public function replaceAction()
    {
        $ids = $this->getParam("ids");
        $from = $this->getParam("from");
        $to = $this->getParam("to");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        foreach ($ids as $id) {
            $stream = \Component\Streams\Repository\StreamRepository::instance()->find($id);
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
            if (!$this->isEditAllowed($campaign)) {
                $this->throwDeny();
            }
            \Component\Streams\Service\StreamService::instance()->replace($stream, $from, $to);
        }
    }
    public function disableAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $streams = [];
        foreach ($ids as $id) {
            $stream = \Component\Streams\Repository\StreamRepository::instance()->find($id);
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
            if (!$this->isEditAllowed($campaign)) {
                $this->throwDeny();
            }
            \Component\Streams\Service\StreamService::instance()->disableStream($stream);
            $streams[] = $this->serialize($stream, new \Component\Streams\Serializer\StreamSerializer());
        }
        if (count($streams) === 1) {
            return $streams[0];
        }
        return $streams;
    }
    public function enableAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $streams = [];
        foreach ($ids as $id) {
            $stream = \Component\Streams\Repository\StreamRepository::instance()->find($id);
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
            if (!$this->isEditAllowed($campaign)) {
                $this->throwDeny();
            }
            \Component\Streams\Service\StreamService::instance()->restoreStream($stream);
            $streams[] = $this->serialize($stream, new \Component\Streams\Serializer\StreamSerializer());
        }
        if (count($streams) === 1) {
            return $streams[0];
        }
        return $streams;
    }
    public function createInCampaignAction()
    {
        $streams = $this->getParam("streams");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($this->getParam("campaign_id"));
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        if ($this->isTrial() && !\Component\Streams\Repository\StreamRepository::instance()->checkTrialStream($campaign->getId(), count($streams))) {
            $this->throwDenyBecauseTrial();
        }
        $result = [];
        foreach ($streams as $data) {
            $data["campaign_id"] = $campaign->getId();
            $result[] = \Component\Streams\Service\StreamService::instance()->create($data);
        }
        \Component\Campaigns\Service\CampaignService::instance()->resortStreams($campaign);
        return $this->serialize($result, new \Component\Streams\Serializer\StreamSerializer());
    }
    public function searchAction()
    {
        $query = $this->getParam("query");
        $ids = \Component\Users\Service\AclService::instance()->getAllowedCampaignIds($this->getUser());
        if ($ids == \Component\Users\Service\AclService::ALLOW_NONE) {
            return [];
        }
        if ($ids == \Component\Users\Service\AclService::ALLOW_ANY) {
            $ids = NULL;
        }
        $case = new \Component\Streams\SearchStreams($ids);
        return $case->search($query);
    }
    public function currentLimitValuesAction()
    {
        $campaignId = (int) $this->getParam("campaign_id");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        $streams = \Component\Streams\Repository\StreamRepository::instance()->all("campaign_id = " . \Core\Db\Db::quote($campaignId));
        $result = [];
        foreach ($streams as $stream) {
            $result[$stream->getId()] = \Traffic\HitLimit\Service\HitLimitService::instance()->getState($stream, new \DateTime());
        }
        return $result;
    }
    public function importAction()
    {
        $request = $this->getServerRequest()->withHeader(\Traffic\Request\ServerRequest::HEADER_X_REQUESTED_WITH, \Traffic\Request\ServerRequest::XMLHTTPREQUEST);
        $this->setServerRequest($request);
        $files = $this->getServerRequest()->getUploadedFiles();
        if (!empty($files["file"]) && !$files["file"]->getError()) {
            $fileContent = (int) $files["file"]->getStream();
        }
        $id = (int) $this->getPostParam("campaign_id");
        $save = $this->getPostParam("save");
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($id);
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        if (empty($fileContent)) {
            $this->status(406);
            return ["errors" => [$this->t("streams.import.empty_file")]];
        }
        if ($this->isTrial()) {
            $items = json_decode($fileContent, true);
            if (!empty($items) && (!\Component\Streams\Service\StreamService::instance()->checkTrialStreamFilters(["filters" => $items]) || !\Component\Streams\Repository\StreamRepository::instance()->checkTrialStream($campaign->getId(), count($items)))) {
                $this->throwDenyBecauseTrial();
            }
        }
        $importer = new \Component\Streams\ImportStreams();
        $result = $importer->import($campaign, $fileContent, $save);
        return $result;
    }
    public function exportAction()
    {
        $campaignId = intval($this->getPostParam("campaign_id"));
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
        if (!$this->isViewAllowed($campaign)) {
            $this->throwDeny();
        }
        $exporter = new \Component\Streams\ExportStreams($campaign);
        $fileName = $exporter->export();
        return ["url" => \Traffic\Service\UrlService::instance()->getBaseUrl($this->getServerRequest()->getUri()) . $fileName];
    }
    public function archiveAction()
    {
    }
    public function cleanArchiveAction()
    {
        $pruner = new \Component\Streams\PruneTask\PruneStreams();
        $pruner->deleteAll();
    }
}

?>