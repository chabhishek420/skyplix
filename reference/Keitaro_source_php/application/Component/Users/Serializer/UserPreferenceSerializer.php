<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Serializer;

class UserPreferenceSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = ["pref_name", "pref_value"];
}

?>