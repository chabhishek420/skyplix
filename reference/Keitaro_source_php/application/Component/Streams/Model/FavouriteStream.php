<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Model;

class FavouriteStream extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "favourite_streams";
    public static function repository()
    {
        return \Component\Streams\Repository\FavouriteStreamRepository::instance();
    }
    public static function service()
    {
        return \Component\Streams\Service\FavouriteStreamService::instance();
    }
}

?>