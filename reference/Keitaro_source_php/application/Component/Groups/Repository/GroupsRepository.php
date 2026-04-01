<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Groups\Repository;

class GroupsRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Groups\Model\Group::definition();
    }
    public function allAsHash()
    {
        $groups = [];
        foreach (\Core\Db\DataRepository::instance()->rawRows($this->definition()) as $row) {
            $groups[$row["id"]] = $row["name"];
        }
        return $groups;
    }
    public function listAsOptions($groups)
    {
        return \Core\Entity\ListOptions\Builder::build($this->definition(), $groups, []);
    }
    public function allByType($type)
    {
        return \Core\Db\DataRepository::instance()->all($this->definition(), "type = " . \Core\Db\Db::quote($type), "position, id");
    }
    public function getItemsCount(\Component\Groups\Model\Group $group)
    {
        $definition = \Component\Groups\Service\GroupService::instance()->getModelDefinition($group->get("type"));
        return \Core\Db\DataRepository::instance()->count($definition, "group_id = " . \Core\Db\Db::quote($group->getId()));
    }
}

?>