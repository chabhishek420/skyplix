<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Groups\Controller;

class GroupsController extends \Admin\Controller\BaseController
{
    public function listAsOptionsAction()
    {
        $type = $this->getParam("type");
        $groups = \Component\Groups\Repository\GroupsRepository::instance()->allByType($type);
        $groups = $this->filterGroupsByAcl($groups);
        return \Component\Groups\Repository\GroupsRepository::instance()->listAsOptions($groups);
    }
    public function indexAction()
    {
        $type = $this->getParam("type");
        $groups = \Component\Groups\Repository\GroupsRepository::instance()->allByType($type);
        $groups = $this->filterGroupsByAcl($groups);
        return $this->serialize($groups, new \Component\Groups\Serializer\GroupSerializer($this->getParam("extended")));
    }
    public function createAction()
    {
        $type = $this->getPostParam("type");
        if (!$this->isCreateAllowed(\Component\Groups\Service\GroupService::instance()->getAclEntityType($type))) {
            $this->throwDeny();
        }
        $group = \Component\Groups\Service\GroupService::instance()->createGroup($this->getPostParam("name"), $type);
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), [$group], true);
        return $this->serialize($group, new \Component\Groups\Serializer\GroupSerializer());
    }
    public function updateAction()
    {
        $id = $this->getPostParam("id");
        if (empty($id)) {
            $id = $this->getParam("id");
        }
        $group = \Component\Groups\Repository\GroupsRepository::instance()->find($id);
        if (!$this->isEditGroupAllowed($group)) {
            $this->throwDeny();
        }
        $group = \Component\Groups\Service\GroupService::instance()->updateGroup($group, $this->getPostParam("name"), $this->getPostParam("position"));
        return $this->serialize($group, new \Component\Groups\Serializer\GroupSerializer(true));
    }
    public function deleteAction()
    {
        $id = $this->getPostParam("id");
        if (empty($id)) {
            $id = $this->getParam("id");
        }
        $group = \Component\Groups\Repository\GroupsRepository::instance()->find($id);
        if (!$group || !$this->isEditGroupAllowed($group)) {
            $this->throwDeny();
        }
        \Component\Groups\Service\GroupService::instance()->deleteGroup($group);
    }
}

?>