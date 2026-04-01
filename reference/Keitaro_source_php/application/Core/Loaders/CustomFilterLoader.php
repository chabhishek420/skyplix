<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Kernel;

class CustomFilterLoader
{
    public static function fixCode($content)
    {
        $content = str_replace("Component\\StreamFilters\\Model\\StreamFilter", "Traffic\\Model\\StreamFilter", $content);
        return $content;
    }
    public static function load($path)
    {
        $content = file_get_contents($path);
        $content = self::fixCode($content);
        eval("?>" . $content);
    }
}

?>