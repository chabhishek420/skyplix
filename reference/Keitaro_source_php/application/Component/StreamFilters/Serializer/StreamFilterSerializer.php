<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Serializer;

class StreamFilterSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    public function extra($obj, $data)
    {
        $data["oid"] = $data["id"];
        if ($data["name"] == "uniqueness_cookie" || $data["name"] == "uniqueness_ip") {
            $data["name"] = "uniqueness";
        }
        return $data;
    }
}

?>