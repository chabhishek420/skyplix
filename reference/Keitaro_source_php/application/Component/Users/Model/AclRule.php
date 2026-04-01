<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Model;

class AclRule extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "acl";
    const FULL_ACCESS = "full_access";
    const CREATED_BY_USER_GROUPS_AND_SELECTED = "created_by_user_groups_and_selected";
    const TO_GROUPS_AND_SELECTED = "to_groups_and_selected";
    const READ_ONLY = "read_only";
    const REPORTS = "reports";
    const RESOURCES = "resources";
    public static function validator()
    {
        return new \Component\Users\Validator\AclValidator();
    }
    public static function repository()
    {
        return \Component\Users\Repository\AclRuleRepository::instance();
    }
    public static function service()
    {
        return \Component\Users\Service\AclRuleService::instance();
    }
    public function addEntityPermission($entityId)
    {
        $entities = is_array($this->get("entities")) ? $this->get("entities") : [];
        if (!in_array($entityId, $entities)) {
            $entities[] = $entityId;
            $this->set("entities", $entities);
        }
        return $this;
    }
    public function addGroupPermission($groupId)
    {
        $groups = $this->get("groups");
        if (!in_array($groupId, $groups)) {
            $groups[] = $groupId;
            $this->set("groups", $groups);
        }
        return $this;
    }
    public function checkGroupId($groupId)
    {
        return is_array($this->get("groups")) && in_array($groupId, $this->get("groups"));
    }
    public function checkEntityId($entityId)
    {
        return is_array($this->get("entities")) && in_array($entityId, $this->get("entities"));
    }
    public function createAllowed()
    {
        return in_array($this->get("access_type"), [FULL_ACCESS, CREATED_BY_USER_GROUPS_AND_SELECTED]);
    }
    public function getAccessType()
    {
        return $this->get("access_type");
    }
    public function getEntities()
    {
        return $this->get("entities");
    }
    public function getGroups()
    {
        return $this->get("groups");
    }
    public function getEntityType()
    {
        return $this->get("entity_type");
    }
}

?>