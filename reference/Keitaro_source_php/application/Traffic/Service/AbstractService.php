<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Service;

class AbstractService
{
    protected static $_instances = [];
    public static function instance()
    {
        $className = get_called_class();
        if (!isset(self::$_instances[$className])) {
            self::$_instances[$className] = new $className();
        }
        return self::$_instances[$className];
    }
    public static function reset()
    {
        $className = get_called_class();
        self::$_instances[$className] = NULL;
    }
}

?>