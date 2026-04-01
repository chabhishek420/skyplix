<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Groups\Model;

class Group extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "groups";
    protected static $_cacheKey = "group";
    protected static $_aclKey = "group";
    protected static $_entityName = "group";
    const TYPE_OFFER = "offers";
    const TYPE_LANDING = "landings";
    const TYPE_CAMPAIGN = "campaigns";
    public function getName()
    {
        return $this->get("name");
    }
    public static function validator()
    {
        return new \Component\Groups\Validator\GroupValidator();
    }
    public static function repository()
    {
        return \Component\Groups\Repository\GroupsRepository::instance();
    }
    public static function service()
    {
        return \Component\Groups\Service\GroupService::instance();
    }
}

?>