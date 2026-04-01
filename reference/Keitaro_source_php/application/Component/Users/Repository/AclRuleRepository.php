<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Repository;

class AclRuleRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Users\Model\AclRule::definition();
    }
    public function getAllowedIdsByResourceName($getAllowedIdsByResourceName, $resourceName, string $userId)
    {
        $aclRule = $this->findFirst("user_id = " . \Core\Db\Db::quote($userId) . " AND entity_type = " . \Core\Db\Db::quote($resourceName));
        return $aclRule->get("entities");
    }
}

?>