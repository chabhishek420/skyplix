<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Serializer;

class MigrationSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    public function serialize($obj, $exclusions = [])
    {
        return ["name" => $obj->getName(), "description" => $obj->getDescription(\Core\Locale\LocaleService::instance()->getLanguage())];
    }
}

?>