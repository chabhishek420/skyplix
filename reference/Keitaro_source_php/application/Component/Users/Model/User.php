<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Model;

class User extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "users";
    protected static $_entityName = "user";
    const TYPE_ADMIN = "ADMIN";
    const TYPE_USER = "USER";
    public static function validator()
    {
        return new \Component\Users\Validator\UserValidator();
    }
    public static function repository()
    {
        return \Component\Users\Repository\UserRepository::instance();
    }
    public static function service()
    {
        return \Component\Users\Service\UserService::instance();
    }
    public function getLogin()
    {
        return $this->get("login");
    }
    public function isAdmin()
    {
        return $this->get("type") == "ADMIN";
    }
    public function getType()
    {
        return $this->get("type");
    }
    public function getPassword()
    {
        return $this->get("password");
    }
    public function getPasswordHash()
    {
        return $this->get("password_hash");
    }
    public function getPermissions()
    {
        return $this->get("permissions");
    }
}

?>