<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Model;

class UserPreference extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "user_preferences";
    const LANGUAGE = "language";
    const TIMEZONE = "timezone";
    public static function validator()
    {
        return new \Component\Users\Validator\UserPreferenceValidator();
    }
    public static function service()
    {
        return \Component\Users\Service\UserPreferenceService::instance();
    }
}

?>