<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Service;

class StreamService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Traffic\Model\Stream::definition();
    }
    public function cloneTo(\Traffic\Model\BaseStream $stream, \Traffic\Model\Campaign $toCampaign)
    {
        $data = ["campaign_id" => $toCampaign->getId(), "position" => $stream->getPosition(), "weight" => $stream->getWeight(), "name" => $stream->get("name")];
        if ($toCampaign->getId() == $stream->getCampaignId()) {
            $data["name"] = \Core\Entity\Service\EntityService::COPY_OF . $data["name"];
        }
        $newStream = $this->cloneEntity($stream, $data);
        $landings = \Component\Streams\Repository\StreamLandingAssociationRepository::instance()->allByStream($stream);
        $landingsData = [];
        foreach ($landings as $landing) {
            $data = $landing->getData();
            unset($data["id"]);
            $landingsData[] = $data;
        }
        \Component\Landings\Service\StreamLandingAssociationService::instance()->assign($newStream, $landingsData);
        $offers = \Component\Streams\Repository\StreamOfferAssociationRepository::instance()->allByStream($stream);
        $offersData = [];
        foreach ($offers as $offer) {
            $data = $offer->getData();
            unset($data["id"]);
            $offersData[] = $data;
        }
        \Component\Offers\Service\StreamOfferAssociationService::instance()->assign($newStream, $offersData);
        $triggers = \Component\Triggers\Repository\TriggersRepository::instance()->allByStream($stream);
        foreach ($triggers as $trigger) {
            \Component\Triggers\Service\TriggerService::instance()->cloneEntity($trigger, ["stream_id" => $newStream->getId()]);
        }
        $filters = \Component\StreamFilters\Repository\StreamFilterRepository::instance()->allByStream($stream);
        foreach ($filters as $filter) {
            \Component\StreamFilters\Service\StreamFilterService::instance()->cloneEntity($filter, ["stream_id" => $newStream->getId()]);
        }
        $newStream = \Component\Streams\Repository\StreamRepository::instance()->find($newStream->getId());
        return $newStream;
    }
    public function replace(\Traffic\Model\BaseStream $stream, $from, $to)
    {
        $url = str_ireplace($from, $to, $stream->getActionPayload());
        $stream = $this->update($stream, ["action_payload" => $url]);
        return $stream;
    }
    public function delete(\Core\Entity\Model\EntityModelInterface $entity)
    {
        \Component\StreamFilters\Service\StreamFilterService::instance()->deleteByStream($entity);
        \Component\Triggers\Service\TriggerService::instance()->deleteByStream($entity);
        \Component\Landings\Service\StreamLandingAssociationService::instance()->deleteByStream($entity);
        \Component\Offers\Service\StreamOfferAssociationService::instance()->deleteByStream($entity);
        self::delete($entity);
    }
    public function create($data)
    {
        if (empty($data["type"])) {
            $data["type"] = \Traffic\Model\Stream::TYPE_REGULAR;
        }
        if (empty($data["weight"])) {
            $data["weight"] = 0;
        }
        if ($data["type"] == \Traffic\Model\Stream::TYPE_DEFAULT) {
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($data["campaign_id"]);
            if (count(\Component\Streams\Repository\StreamRepository::instance()->allActiveForTypeAndCampaign(\Traffic\Model\Stream::TYPE_DEFAULT, $campaign))) {
                throw new \Core\Validator\ValidationError(["type" => [\Core\Locale\LocaleService::t("streams.only_one_default_stream")]]);
            }
        }
        if (isset($data["action_type"]) && $data["action_type"] == "do_nothing") {
            $data["action_payload"] = "";
        }
        $stream = self::create($data);
        $this->_updateAssociations($stream, $data);
        return $stream;
    }
    public function update(\Core\Entity\Model\EntityModelInterface $stream, $data)
    {
        if (isset($data["action_type"]) && $data["action_type"] == "do_nothing") {
            $data["action_payload"] = "";
        }
        $stream = self::update($stream, $data);
        $this->_updateAssociations($stream, $data);
        return $stream;
    }
    private function _updateAssociations(\Traffic\Model\BaseStream $stream, $params)
    {
        if ($stream->getType() == \Traffic\Model\Stream::TYPE_DEFAULT) {
            $params["filters"] = [];
            $params["triggers"] = [];
        }
        if ($stream->getSchema() == \Traffic\Model\BaseStream::ACTION || $stream->getSchema() == \Traffic\Model\BaseStream::REDIRECT) {
            $params["landings"] = [];
            $params["offers"] = [];
        }
        if (array_key_exists("landings", $params)) {
            \Component\Landings\Service\StreamLandingAssociationService::instance()->assign($stream, $params["landings"]);
        }
        if (array_key_exists("offers", $params)) {
            \Component\Offers\Service\StreamOfferAssociationService::instance()->assign($stream, $params["offers"]);
        }
        if (array_key_exists("filters", $params)) {
            \Component\StreamFilters\Service\StreamFilterService::instance()->assign($stream, $params["filters"]);
        }
        if (array_key_exists("triggers", $params)) {
            \Component\Triggers\Service\StreamTriggerService::instance()->assign($stream, $params["triggers"]);
        }
    }
    public function archiveStream(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $this->archive($entity);
        \Component\Campaigns\Service\CampaignService::instance()->resortStreams(\Component\Campaigns\Repository\CampaignRepository::instance()->find($entity->getCampaignId()));
    }
    public function restoreStream(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $this->makeActive($entity);
        \Component\Campaigns\Service\CampaignService::instance()->resortStreams(\Component\Campaigns\Repository\CampaignRepository::instance()->find($entity->getCampaignId()));
    }
    public function resortStreams(\Traffic\Model\Campaign $campaign)
    {
        $streams = \Component\Streams\Repository\StreamRepository::instance()->allActiveForTypeAndCampaign(\Traffic\Model\Stream::TYPE_REGULAR, $campaign);
        if ($campaign->getCampaignType() == \Traffic\Model\Campaign::TYPE_POSITION) {
            $position = 1;
            foreach ($streams as $stream) {
                if ($stream->getPosition() != $position) {
                    $stream->setPosition($position);
                    \Core\Db\DataService::instance()->save($this->definition(), $stream);
                }
                $position++;
            }
        }
    }
    public function checkTrialStreamFilters($streamData)
    {
        return empty($streamData["filters"]) || count($streamData["filters"]) <= 2;
    }
    public function updateStreams(\Traffic\Model\Campaign $campaign, $streams)
    {
        \Core\Db\Db::instance()->beginTransaction();
        $oldStreams = \Component\Streams\Repository\StreamRepository::instance()->allOrderedStreamsForCampaign($campaign);
        $newStreamIds = [];
        $createStreams = [];
        $resultStreams = [];
        foreach ($streams as $stream) {
            $stream = $this->_patchWeight($campaign->getCampaignType(), $stream);
            if (isset($stream["id"])) {
                $newStreamIds[$stream["id"]] = $stream;
            } else {
                $createStreams[] = $stream;
            }
        }
        foreach ($oldStreams as $oldStream) {
            $oldStreamId = $oldStream->getId();
            if (!isset($newStreamIds[$oldStreamId])) {
                $this->archiveStream($oldStream);
            } else {
                $resultStreams[] = $this->update($oldStream, $newStreamIds[$oldStreamId]);
            }
            unset($newStreamIds[$oldStreamId]);
        }
        foreach ($newStreamIds as $newStream) {
            $createStreams[] = $newStream;
        }
        foreach ($createStreams as $createStream) {
            $createStream["campaign_id"] = $campaign->getId();
            $resultStreams[] = $this->create($createStream);
        }
        \Core\Db\Db::instance()->commit();
        return $resultStreams;
    }
    public function disableStream(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $entity = $this->disable($entity);
        \Component\Campaigns\Service\CampaignService::instance()->resortStreams(\Component\Campaigns\Repository\CampaignRepository::instance()->find($entity->getCampaignId()));
    }
    private function _patchWeight($_patchWeight, $campaignType, $stream)
    {
        if ($campaignType === \Traffic\Model\Campaign::TYPE_WEIGHT && !array_key_exists("weight", $stream)) {
            $stream["weight"] = $stream["position"];
        }
        return $stream;
    }
}

?>