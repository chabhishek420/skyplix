<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Serializer;

class FavouriteReportSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    public function extra($obj, $data)
    {
        unset($data["user_id"]);
        return $data;
    }
}

?>