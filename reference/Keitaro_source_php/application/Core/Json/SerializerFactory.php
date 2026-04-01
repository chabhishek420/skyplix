<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Json;

class SerializerFactory
{
    public static function serialize($payload, SerializerInterface $serializer)
    {
        $serializer->prepare($payload);
        if (is_array($payload)) {
            $result = [];
            foreach ($payload as $object) {
                $result[] = $serializer->serialize($object);
            }
            return $result;
        } else {
            return $serializer->serialize($payload);
        }
    }
}

?>