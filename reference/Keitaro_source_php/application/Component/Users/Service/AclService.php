<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Service;

class AclService extends \Traffic\Service\AbstractService
{
    const EDIT = "edit";
    const VIEW = "view";
    const ALLOW_ANY = "allow_any";
    const ALLOW_NONE = "allow_none";
    public function filterByAcl($entityList, $forEdit = false, \Component\Users\Model\User $user = NULL)
    {
        if ($forEdit) {
            $operationType = "edit";
        } else {
            $operationType = "view";
        }
        return $this->filter($user, $entityList, $operationType, false);
    }
    public function saveAcl(\Component\Users\Model\User $user, $aclData)
    {
        if (!empty($aclData["resources"])) {
            $resources = $aclData["resources"];
        } else {
            $resources = [];
        }
        $result = [];
        foreach ($aclData as $key => $value) {
            if (preg_match("/^(.+)_access_type\$/", $key, $matches)) {
                $result[$matches[1]]["type"] = $value;
            }
            if (preg_match("/^(.+)_selected_groups\$/", $key, $matches)) {
                $result[$matches[1]]["groups"] = $value;
            }
            if (preg_match("/^(.+)_selected_entities\$/", $key, $matches)) {
                $result[$matches[1]]["entities"] = $value;
            }
        }
        foreach ($result as $key => $value) {
            if (!in_array($key, $resources)) {
                unset($result[$key]);
            }
        }
        foreach ($resources as $resource) {
            if (is_string($resource) && !isset($result[$resource])) {
                $result[$resource]["type"] = \Component\Users\Model\AclRule::READ_ONLY;
            }
        }
        if (!empty($aclData["reports"])) {
            $reports = $aclData["reports"];
        } else {
            $reports = [];
        }
        $this->_saveEntityAccess($result, $user);
        $this->_saveReports($reports, $user);
        $this->_saveResources($resources, $user);
    }
    protected function _saveEntityAccess($access, \Component\Users\Model\User $user)
    {
        $accessRows = \Component\Users\Repository\AclRuleRepository::instance()->all("user_id = " . \Core\Db\Db::quote($user->getId()));
        foreach ($accessRows as $accessRow) {
            $entityType = $accessRow->getEntityType();
            if (is_string($entityType) && !isset($access[$entityType])) {
                \Core\Db\DataService::instance()->delete(\Component\Users\Model\AclRule::definition(), $accessRow);
            }
        }
        foreach ($access as $key => $data) {
            $accessRow = \Component\Users\Repository\AclRuleRepository::instance()->findFirst("user_id = " . \Core\Db\Db::quote($user->getId()) . " AND entity_type = " . \Core\Db\Db::quote($key));
            if (isset($data["type"]) && $data["type"] !== "") {
                $type = $data["type"];
            } else {
                \Traffic\Logging\Service\LoggerService::instance()->warning("Empty ACL type: " . print_r($data, true));
                $type = \Component\Users\Model\AclRule::READ_ONLY;
            }
            $groups = isset($data["groups"]) ? $data["groups"] : NULL;
            $entities = isset($data["entities"]) ? $data["entities"] : NULL;
            if (empty($accessRow)) {
                AclRuleService::instance()->create(["user_id" => $user->getId(), "entity_type" => $key, "access_type" => $type, "groups" => $groups, "entities" => $entities]);
            } else {
                $accessRow->set("access_type", $type)->set("groups", $groups)->set("entities", $entities);
                \Core\Db\DataService::instance()->save(\Component\Users\Model\AclRule::definition(), $accessRow);
            }
        }
    }
    protected function _saveReports($reports, \Component\Users\Model\User $user)
    {
        $accessRow = \Component\Users\Repository\AclReportRepository::instance()->findFirst("user_id = " . \Core\Db\Db::quote($user->getId()));
        if (empty($accessRow)) {
            if (!empty($reports)) {
                AclReportService::instance()->create(["user_id" => $user->getId(), "columns" => $reports]);
            }
        } else {
            AclReportService::instance()->update($accessRow, ["columns" => $reports]);
        }
    }
    protected function _saveResources($resources, \Component\Users\Model\User $user)
    {
        $accessRow = \Component\Users\Repository\AclResourceRepository::instance()->findFirst("user_id = " . \Core\Db\Db::quote($user->getId()));
        if (empty($accessRow)) {
            if (!empty($resources)) {
                AclResourceService::instance()->create(["user_id" => $user->getId(), "resources" => $resources]);
            }
        } else {
            AclResourceService::instance()->update($accessRow, ["resources" => $resources]);
        }
    }
    protected function _getAcl(\Component\Users\Model\User $user, $entityType)
    {
        return \Component\Users\Repository\AclRuleRepository::instance()->findFirst("user_id = " . \Core\Db\Db::quote($user->getId()) . " AND entity_type = " . \Core\Db\Db::quote($entityType));
    }
    public function getByUser(\Component\Users\Model\User $user)
    {
        if (!\Core\Db\DataRepository::instance()->tableExists(\Component\Users\Model\AclRule::definition())) {
            return NULL;
        }
        $result = [];
        $aclRules = $this->getAclRulesByUser($user);
        $result = array_merge($result, $aclRules);
        $report = \Component\Users\Repository\AclReportRepository::instance()->findFirst("user_id = " . \Core\Db\Db::quote($user->getId()));
        if (!empty($report)) {
            $result["reports"] = $report->get("columns");
        }
        if (\Core\Db\DataRepository::instance()->tableExists(\Component\Users\Model\AclRule::definition())) {
            $resources = $this->_getResourcesByUser($user);
            if (!empty($resources)) {
                $result["resources"] = $resources;
                $result["allowedResources"] = \Component\Users\Repository\AclResourceRepository::instance()->expandList($resources);
            }
        }
        return $result;
    }
    public function getAclRulesByUser(\Component\Users\Model\User $user)
    {
        $aclRules = [];
        if ($user->isAdmin()) {
            $resources = \Component\Users\Repository\AclResourceRepository::instance()->getDefaultForNewUsers();
            foreach ($resources as $resource) {
                $aclRules[$resource . "_access_type"] = \Component\Users\Model\AclRule::FULL_ACCESS;
            }
        } else {
            $rows = \Component\Users\Repository\AclRuleRepository::instance()->all("user_id = " . \Core\Db\Db::quote($user->getId()));
            foreach ($rows as $row) {
                $entityName = $row->get("entity_type");
                $aclRules[$entityName . "_access_type"] = $row->get("access_type");
                $groups = $row->get("groups");
                if (isset($groups)) {
                    $aclRules[$entityName . "_selected_groups"] = $groups;
                }
                $entities = $row->get("entities");
                if (isset($entities)) {
                    $aclRules[$entityName . "_selected_entities"] = $entities;
                }
            }
        }
        return $aclRules;
    }
    private function _getResourcesByUser(\Component\Users\Model\User $user)
    {
        $resource = \Component\Users\Repository\AclResourceRepository::instance()->findFirst("user_id = " . \Core\Db\Db::quote($user->getId()));
        if (!empty($resource)) {
            return $resource->get("resources");
        }
        return [];
    }
    public function getByUserId($userId)
    {
        $user = \Component\Users\Repository\UserRepository::instance()->find($userId);
        if (!empty($user)) {
            return $this->getByUser($user);
        }
        return NULL;
    }
    public function onEntityDelete($entityType, $entityId)
    {
        $rows = \Component\Users\Repository\AclRuleRepository::instance()->all("entity_type = " . \Core\Db\Db::quote($entityType));
        foreach ($rows as $acl) {
            $entityArray = $acl->getEntities();
            if (!empty($entityArray) && array_search($entityId, $entityArray) !== false) {
                $acl->set("entities", array_values(array_diff($entityArray, [$entityId])));
                \Core\Db\DataService::instance()->save(\Component\Users\Model\AclRule::definition(), $acl);
            }
        }
    }
    public function onGroupDelete($entityType, $groupId)
    {
        $rows = \Component\Users\Repository\AclRuleRepository::instance()->all("entity_type = " . \Core\Db\Db::quote($entityType));
        foreach ($rows as $acl) {
            $groupArray = $acl->getGroups();
            if (!empty($groupArray) && array_search($groupId, $groupArray) !== false) {
                $acl->set("groups", array_values(array_diff($groupArray, [$groupId])))->save();
            }
        }
    }
    public function classNameToEntityType($className)
    {
        if ($className == "Traffic\\Model\\Campaign") {
            return \Traffic\Model\Campaign::aclKey();
        }
        if ($className == "Traffic\\Model\\AffiliateNetwork") {
            return \Traffic\Model\AffiliateNetwork::aclKey();
        }
        if ($className == "Traffic\\Model\\Landing") {
            return \Traffic\Model\Landing::aclKey();
        }
        if ($className == "Traffic\\Model\\Offer") {
            return \Traffic\Model\Offer::aclKey();
        }
        if ($className == "Traffic\\Model\\TrafficSource") {
            return \Traffic\Model\TrafficSource::aclKey();
        }
        if ($className == "Traffic\\Model\\Domain") {
            return \Traffic\Model\Domain::aclKey();
        }
        throw new \Core\Application\Exception\Error("Unknown class " . $className);
    }
    public function isCreateAllowed(\Component\Users\Model\User $user, $entityType)
    {
        if (!$user) {
            throw new \Core\Application\Exception\Error("Unauthenticated user");
        }
        if ($user->isAdmin()) {
            return true;
        }
        $acl = $this->_getAcl($user, $entityType);
        if (empty($acl)) {
            return false;
        }
        return $acl->createAllowed();
    }
    protected function _getEntityTypeFromList($entityList, $isGroup)
    {
        $firstEntity = $entityList[0];
        if ($isGroup) {
            return \Component\Groups\Service\GroupService::instance()->getAclEntityType($firstEntity->get("type"));
        }
        $class = get_class($firstEntity);
        return $class::aclKey();
    }
    public function filter(\Component\Users\Model\User $user, $entityList, $operationType, $isGroup)
    {
        if (!$user) {
            throw new \Core\Application\Exception\Error("Unauthenticated user");
        }
        if ($user->isAdmin()) {
            return $entityList;
        }
        if (empty($entityList)) {
            return [];
        }
        $entityType = $this->_getEntityTypeFromList($entityList, $isGroup);
        $acl = $this->_getAcl($user, $entityType);
        if (empty($acl)) {
            return [];
        }
        if ($acl->getAccessType() == \Component\Users\Model\AclRule::FULL_ACCESS || $acl->getAccessType() == \Component\Users\Model\AclRule::READ_ONLY && $operationType == VIEW) {
            return $entityList;
        }
        return $this->_filterByAcl($acl, $entityList, $isGroup);
    }
    public function groupEntityType($entityType)
    {
        return in_array($entityType, [\Traffic\Model\Campaign::aclKey(), \Traffic\Model\Offer::aclKey(), \Traffic\Model\Landing::aclKey()]);
    }
    protected function _filterByAcl(\Component\Users\Model\AclRule $acl, $entityList, $isGroup)
    {
        $result = [];
        if ($isGroup) {
            if (!$this->groupEntityType($acl->getEntityType())) {
                return $result;
            }
            foreach ($entityList as $group) {
                if ($acl->checkGroupId($group->getId())) {
                    $result[] = $group;
                }
            }
        } else {
            foreach ($entityList as $entity) {
                if ($this->groupEntityType($acl->getEntityType()) && $acl->checkGroupId($entity->get("group_id"))) {
                    $result[] = $entity;
                } else {
                    if ($acl->checkEntityId($entity->getId())) {
                        $result[] = $entity;
                    }
                }
            }
        }
        return $result;
    }
    public function addAuthorPermission(\Component\Users\Model\User $user, $entityList, $isGroup)
    {
        if (!$user) {
            throw new \Core\Application\Exception\Error("Unauthenticated user");
        }
        if ($user->isAdmin()) {
            return NULL;
        }
        if (empty($entityList)) {
            return NULL;
        }
        $entityType = $this->_getEntityTypeFromList($entityList, $isGroup);
        $acl = $this->_getAcl($user, $entityType);
        if (empty($acl) || !$acl->createAllowed()) {
            return NULL;
        }
        $this->_addAclPermission($entityList, $acl, $isGroup);
        $acl->save();
    }
    protected function _addAclPermission($entityList, \Component\Users\Model\AclRule $acl, $isGroup)
    {
        if ($isGroup) {
            foreach ($entityList as $group) {
                $acl->addGroupPermission($group->getId());
            }
        } else {
            foreach ($entityList as $entity) {
                $acl->addEntityPermission($entity->getId());
            }
        }
    }
    public function getAllowedCampaignIds(\Component\Users\Model\User $user)
    {
        if (!$user) {
            throw new \Core\Application\Exception\Error("Unauthenticated user");
        }
        if ($user->isAdmin()) {
            return ALLOW_ANY;
        }
        $acl = $this->_getAcl($user, \Traffic\Model\Campaign::aclKey());
        if (empty($acl)) {
            return ALLOW_NONE;
        }
        if ($acl->getAccessType() == \Component\Users\Model\AclRule::FULL_ACCESS || $acl->getAccessType() == \Component\Users\Model\AclRule::READ_ONLY) {
            return ALLOW_ANY;
        }
        $result = $acl->getEntities();
        if (empty($result)) {
            $result = [];
        }
        $groupIds = $acl->getGroups();
        if (!empty($groupIds)) {
            $result = array_merge($result, \Component\Campaigns\Repository\CampaignRepository::instance()->findByGroupIds($groupIds));
        }
        if (empty($result)) {
            return ALLOW_NONE;
        }
        return $result;
    }
    public function getRestrictedReportColumns(\Component\Users\Model\User $user)
    {
        if (!$user) {
            throw new \Core\Application\Exception\Error("Unauthenticated user");
        }
        if ($user->isAdmin()) {
            return [];
        }
        $acl = \Component\Users\Repository\AclReportRepository::instance()->findFirst("user_id = " . \Core\Db\Db::quote($user->getId()));
        if (empty($acl)) {
            return ALLOW_ANY;
        }
        return $acl->getColumns();
    }
    public function isResourceAllowed(\Component\Users\Model\User $user, $resource)
    {
        if (!is_string($resource)) {
            return false;
        }
        $resource = \Component\Users\Repository\AclResourceRepository::instance()->getRootResource($resource);
        $mandatoryResources = \Component\Users\Repository\AclResourceRepository::instance()->getMandatory();
        if (in_array($resource, $mandatoryResources)) {
            return true;
        }
        if (!$user) {
            throw new \Core\Application\Exception\Error("Unauthenticated user");
        }
        if ($user->isAdmin()) {
            return true;
        }
        $acl = \Component\Users\Repository\AclResourceRepository::instance()->findFirst("user_id = " . \Core\Db\Db::quote($user->getId()));
        if (empty($acl)) {
            return false;
        }
        return in_array($resource, $acl->getResources());
    }
    public function addDefaultAccess(\Component\Users\Model\User $user)
    {
        $resources = \Component\Users\Repository\AclResourceRepository::instance()->getDefaultForNewUsers();
        $this->addResourceAccess($user, $resources);
        $access = [];
        foreach ($resources as $resource) {
            $access[$resource] = ["type" => \Component\Users\Model\AclRule::FULL_ACCESS];
        }
        $this->_saveEntityAccess($access, $user);
    }
    public function addResourceAccess(\Component\Users\Model\User $user, $addResources)
    {
        $resources = $this->_getResourcesByUser($user);
        $resources = array_merge($resources, $addResources);
        $resources = array_unique($resources);
        $this->_saveResources($resources, $user);
    }
}

?>