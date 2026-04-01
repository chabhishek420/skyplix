<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Serializer;

class DecoratedUserSerializer extends UserSerializer
{
    protected $_keys = NULL;
    public function extra($obj, $data)
    {
        $data = array_merge($data, self::extra($obj, $data));
        return $data;
    }
}

?>