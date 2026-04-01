<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Model;

class AclResource extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "acl_resources";
    public static function validator()
    {
        return new \Component\Users\Validator\AclResourceValidator();
    }
    public static function repository()
    {
        return \Component\Users\Repository\AclResourceRepository::instance();
    }
    public static function service()
    {
        return \Component\Users\Service\AclResourceService::instance();
    }
    public function getResources()
    {
        return $this->get("resources");
    }
}

?>