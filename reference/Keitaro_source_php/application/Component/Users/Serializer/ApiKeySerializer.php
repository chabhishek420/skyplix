<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Serializer;

class ApiKeySerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = ["id", "key", "datetime"];
    public function extra($obj, $data)
    {
        if (empty($data["datetime"])) {
            $data["datetime"] = new \DateTime();
        }
        $data["datetime"] = $data["datetime"]->format(\Core\Locale\LocaleService::t("format.datetime"));
        return $data;
    }
}

?>