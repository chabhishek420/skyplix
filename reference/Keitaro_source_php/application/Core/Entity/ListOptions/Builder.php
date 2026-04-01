<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Entity\ListOptions;

class Builder
{
    public static function build(\Core\Entity\Definition\EntityDefinition $definition, $modelList, $addFields)
    {
        $items = [];
        $groupsHash = \Component\Groups\Repository\GroupsRepository::instance()->allAsHash();
        foreach ($modelList as $model) {
            $object = ["value" => (int) $model->getId(), "name" => $model->get("name")];
            if (\Component\Users\Service\AclService::instance()->groupEntityType($definition->entityName())) {
                $object["group_id"] = $model->get("group_id");
                $object["group"] = NULL;
                if (!empty($object["group_id"])) {
                    $object["group"] = $groupsHash[$model->get("group_id")];
                }
            }
            foreach ($addFields as $key => $fieldName) {
                $object[$key] = $model->get($fieldName);
            }
            $items[] = $object;
        }
        return $items;
    }
}

?>