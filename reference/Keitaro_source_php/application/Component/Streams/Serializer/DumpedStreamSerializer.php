<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Serializer;

class DumpedStreamSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    public function extra($obj, $data)
    {
        foreach (\Component\Migrations\Migrator7\TdsMigrator7::getKeitaro6Fields() as $field) {
            unset($data[$field]);
        }
        unset($data["landing_id"]);
        unset($data["offer_id"]);
        return $data;
    }
}

?>