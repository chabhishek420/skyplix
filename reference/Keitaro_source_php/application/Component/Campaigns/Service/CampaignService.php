<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Service;

class CampaignService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Traffic\Model\Campaign::definition();
    }
    public function updatePositions($groupId, $campaigns)
    {
        if (is_array($campaigns)) {
            $position = 0;
            foreach ($campaigns as $campaign) {
                $position++;
                $this->updateCampaign($campaign, ["position" => $position, "group_id" => $groupId ? $groupId : NULL]);
            }
        }
    }
    public function updateCosts($data, $currency, $timezone, $onlyCampaignUniques = false)
    {
        $case = new \Component\Campaigns\UseCase\UpdateCampaignCostBulk();
        if ($onlyCampaignUniques) {
            $case->onlyCampaignUniques(true);
        }
        $case->setData($data)->setCurrency($currency)->setTimezone($timezone)->update();
    }
    public function resortCampaigns()
    {
        $items = \Component\Campaigns\Repository\CampaignRepository::instance()->all(NULL, "position");
        $position = 0;
        foreach ($items as $campaign) {
            $position++;
            $this->update($campaign, ["position" => $position]);
        }
    }
    public function updatePostbacks(\Traffic\Model\Campaign $campaign, $postbacks)
    {
        if (!$campaign->getId()) {
            throw new \Core\Application\Exception\Error("Campaign is not created");
        }
        $exclude = [];
        if (isset($postbacks) && count($postbacks)) {
            foreach ($postbacks as $data) {
                $postback = NULL;
                if (!empty($data["id"])) {
                    $postback = \Component\Campaigns\Repository\CampaignPostbackRepository::instance()->find($data["id"]);
                }
                if (!isset($postback)) {
                    $data["campaign_id"] = $campaign->getId();
                    $postback = CampaignPostbackService::instance()->create($data);
                } else {
                    $postback->setData($data);
                    CampaignPostbackService::instance()->save($postback);
                }
                $exclude[] = $postback->getId();
            }
        }
        \Component\Campaigns\Model\CampaignPostback::deleteByCampaign($campaign, $exclude);
        return $this;
    }
    public function create($data)
    {
        $data = array_merge(["state" => \Core\Entity\State::ACTIVE, "type" => \Traffic\Model\Campaign::TYPE_POSITION, "cost_type" => \Traffic\Model\Campaign::COST_TYPE_CPC, "cost_currency" => \Traffic\Repository\CachedSettingsRepository::instance()->get("currency")], $data);
        if (empty($data["position"])) {
            $data["position"] = 9999;
        }
        if (!empty($data["alias"])) {
            $data["alias"] = str_replace([".", "#"], "-", trim($data["alias"]));
        }
        if ($data["type"] == \Traffic\Model\Campaign::TYPE_POSITION) {
            $data["bind_visitors"] = NULL;
        }
        if (empty($data["token"])) {
            $data["token"] = rand(0, 9999999) . md5(microtime());
        }
        if (empty($data["cookies_ttl"])) {
            $data["cookies_ttl"] = \Traffic\Model\Campaign::DEFAULT_COOKIES_TTL;
        }
        $data["cookies_ttl"] = max($data["cookies_ttl"], \Traffic\Model\Campaign::MIN_COOKIES_TTL);
        $data["cookies_ttl"] = min($data["cookies_ttl"], \Traffic\Model\Campaign::MAX_COOKIES_TTL);
        $campaign = self::create($data);
        if (array_key_exists("postbacks", $data)) {
            $this->updatePostbacks($campaign, $data["postbacks"]);
        }
        return $campaign;
    }
    public function updateCampaign(\Core\Entity\Model\EntityModelInterface $entity, $data)
    {
        if (isset($data["alias"])) {
            $data["alias"] = str_replace([".", "#"], "-", trim($data["alias"]));
        }
        if (!empty($data["cookies_ttl"])) {
            $data["cookies_ttl"] = max($data["cookies_ttl"], \Traffic\Model\Campaign::MIN_COOKIES_TTL);
            $data["cookies_ttl"] = min($data["cookies_ttl"], \Traffic\Model\Campaign::MAX_COOKIES_TTL);
        }
        $entity = $this->update($entity, $data);
        if (array_key_exists("postbacks", $data)) {
            $this->updatePostbacks($entity, $data["postbacks"]);
        }
        return $entity;
    }
    public function enableCampaign(\Core\Entity\Model\EntityModelInterface $entity)
    {
        if ($entity->get("state") != \Core\Entity\State::DELETED) {
            $entity = $this->makeActive($entity);
        }
        return $entity;
    }
    public function delete(\Core\Entity\Model\EntityModelInterface $entity)
    {
        foreach (\Component\Streams\Repository\StreamRepository::instance()->getCampaignStreams($entity) as $stream) {
            \Component\Streams\Service\StreamService::instance()->delete($stream);
        }
        \Component\Domains\Service\DomainService::instance()->nullifyByCampaign($entity);
        \Component\Cleaner\DelayedCommand\DeleteStatsCommand::schedule(["campaign_id" => $entity->getId()]);
        self::delete($entity);
    }
    public function cloneCampaign(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $data = [];
        $data["alias"] = \Traffic\Tools\Tools::generateRandomString(8);
        $data["token"] = \Traffic\Tools\Tools::generateRandomString(16);
        $data["name"] = \Core\Entity\Service\EntityService::COPY_OF . $entity->get("name");
        $newCampaign = self::cloneEntity($entity, $data);
        foreach (\Component\Streams\Repository\StreamRepository::instance()->getCampaignStreams($entity) as $stream) {
            \Component\Streams\Service\StreamService::instance()->cloneTo($stream, $newCampaign);
        }
        $this->resortStreams($newCampaign);
        foreach (\Component\Campaigns\Repository\CampaignPostbackRepository::instance()->getCampaignPostbacks($entity) as $postback) {
            CampaignPostbackService::instance()->cloneEntity($postback, ["campaign_id" => $newCampaign->getId()]);
        }
        return $newCampaign;
    }
    public function disableCampaign(\Core\Entity\Model\EntityModelInterface $entity)
    {
        if ($entity->get("state") != \Core\Entity\State::DELETED) {
            $entity = $this->disable($entity);
        }
        \Component\Domains\Service\DomainService::instance()->nullifyByCampaign($entity);
        \Traffic\Service\SettingsService::instance()->resetDefaultActionOnCampaignDisable($entity->getId());
        return $entity;
    }
    public function archive(\Core\Entity\Model\EntityModelInterface $entity)
    {
        \Component\Domains\Service\DomainService::instance()->nullifyByCampaign($entity);
        \Traffic\Service\SettingsService::instance()->resetDefaultActionOnCampaignDisable($entity->getId());
        return self::archive($entity);
    }
    public function unbindTrafficSource($tsId)
    {
        $where = "traffic_source_id = " . \Core\Db\Db::quote($tsId);
        $this->updateMany($where, ["traffic_source_id" => NULL]);
    }
    public function resortStreams(\Traffic\Model\Campaign $campaign)
    {
        if ($campaign->getCampaignType() === \Traffic\Model\Campaign::TYPE_POSITION) {
            $tableName = \Traffic\Model\Stream::definition()->tableName();
            $types = [\Traffic\Model\Stream::TYPE_REGULAR, \Traffic\Model\Stream::TYPE_FORCED];
            foreach ($types as $type) {
                \Core\Db\Db::instance()->execute("SET @pos=0");
                $sql = "UPDATE " . $tableName . " SET position= @pos:= (@pos+1)\n                WHERE campaign_id = " . $campaign->getId() . "\n                   AND `type` = " . \Core\Db\Db::quote($type) . "   \n                   AND state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED) . "\n                ORDER BY position, updated_at";
                \Core\Db\Db::instance()->execute($sql);
            }
        }
    }
}

?>