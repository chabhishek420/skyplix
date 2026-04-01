<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Groups\Service;

class GroupService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Groups\Model\Group::definition();
    }
    public function resort()
    {
        $pos = 0;
        $groups = \Component\Groups\Repository\GroupsRepository::instance()->all(NULL, "position");
        foreach ($groups as $group) {
            $this->update($group, ["position" => ++$pos]);
        }
    }
    public function getNextPosition($type)
    {
        $group = \Component\Groups\Repository\GroupsRepository::instance()->findFirst("type = " . \Core\Db\Db::quote($type), "position, id");
        if (empty($group)) {
            return 1;
        }
        return $group->get("position") + 1;
    }
    public function createGroup($name, $type)
    {
        $group = $this->create(["name" => $name, "position" => $this->getNextPosition($type), "type" => $type]);
        return $group;
    }
    public function updateGroup($group, $name, $position)
    {
        $currentPosGroup = \Component\Groups\Repository\GroupsRepository::instance()->findFirst("position = " . \Core\Db\Db::quote($position));
        if ($currentPosGroup) {
            $this->update($currentPosGroup, ["position" => $position + 1]);
        }
        $data = [];
        if ($position) {
            $data["position"] = $position;
        }
        $data["name"] = $name;
        $this->update($group, $data);
        $this->resort();
        return $group;
    }
    public function deleteGroup(\Core\Entity\Model\EntityModelInterface $entity)
    {
        if ($entity->get("type") == \Component\Groups\Model\Group::TYPE_CAMPAIGN) {
            $campaigns = \Component\Campaigns\Repository\CampaignRepository::instance()->all("group_id = " . \Core\Db\Db::quote($entity->getId()));
            foreach ($campaigns as $campaign) {
                $campaign->set("group_id", NULL)->save();
            }
        } else {
            if ($entity->get("type") == \Component\Groups\Model\Group::TYPE_LANDING) {
                $landings = \Component\Landings\Repository\LandingRepository::instance()->all("group_id = " . \Core\Db\Db::quote($entity->getId()));
                foreach ($landings as $landing) {
                    $landing->set("group_id", NULL)->save();
                }
            } else {
                if ($entity->get("type") == \Component\Groups\Model\Group::TYPE_OFFER) {
                    $offers = \Component\Offers\Repository\OfferRepository::instance()->all("group_id = " . \Core\Db\Db::quote($entity->getId()));
                    foreach ($offers as $offer) {
                        $offer->set("group_id", NULL)->save();
                    }
                }
            }
        }
        \Component\Users\Service\AclService::instance()->onGroupDelete($entity->get("type"), $entity->getId());
        self::delete($entity);
    }
    public function getModelDefinition($type)
    {
        switch ($type) {
            case \Component\Groups\Model\Group::TYPE_CAMPAIGN:
                return \Traffic\Model\Campaign::definition();
                break;
            case \Component\Groups\Model\Group::TYPE_LANDING:
                return \Traffic\Model\Landing::definition();
                break;
            case \Component\Groups\Model\Group::TYPE_OFFER:
                return \Traffic\Model\Offer::definition();
                break;
            default:
                throw new \Exception("Unknown group type");
        }
    }
    public function getAclEntityType($type)
    {
        return $this->getModelDefinition($type)->aclKey();
    }
}

?>