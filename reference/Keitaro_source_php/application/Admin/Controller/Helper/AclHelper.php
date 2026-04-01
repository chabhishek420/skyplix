<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Controller\Helper;

interface AclHelper
{
    public function getUser();
    public function isAdmin();
    public function isCreateAllowed($entityType);
    public function isEditGroupAllowed(\Component\Groups\Model\Group $group);
    public function isViewGroupAllowed(\Component\Groups\Model\Group $group);
    public function isEditAllowed($entity);
    public function isViewAllowed($entity);
    public function filterGroupsByAcl($groupList, $forEdit);
}

?>