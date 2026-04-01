<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Model;

class UserPasswordHash extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "user_password_hashes";
    public static function validator()
    {
        return new \Component\Users\Validator\UserPasswordHashValidator();
    }
    public static function repository()
    {
        return \Component\Users\Repository\UserPasswordHashRepository::instance();
    }
}

?>