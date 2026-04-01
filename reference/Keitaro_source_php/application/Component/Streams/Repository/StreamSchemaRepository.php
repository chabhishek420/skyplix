<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Repository;

class StreamSchemaRepository extends \Traffic\Repository\AbstractBaseRepository
{
    public function getSchemas()
    {
        return [\Traffic\Model\BaseStream::LANDINGS, \Traffic\Model\BaseStream::REDIRECT, \Traffic\Model\BaseStream::ACTION];
    }
    public function getListAsOptions()
    {
        $options = [];
        foreach ($this->getSchemas() as $name) {
            $options[] = ["value" => $name, "name" => \Core\Locale\LocaleService::t("streams.schemas." . $name), "description" => \Core\Locale\LocaleService::t("streams.schemas." . $name . "_desc")];
        }
        return $options;
    }
}

?>