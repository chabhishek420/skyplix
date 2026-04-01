<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Model\Ref;

class SubIdN extends AbstractRef
{
    protected static $_fields = NULL;
    protected static $_tableName = "ref_sub_ids";
    public static function service()
    {
        return \Component\Clicks\Service\SubIdNService::instance();
    }
}

?>