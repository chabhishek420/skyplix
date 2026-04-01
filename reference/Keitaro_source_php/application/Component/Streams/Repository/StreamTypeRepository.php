<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Repository;

class StreamTypeRepository extends \Traffic\Repository\AbstractBaseRepository
{
    const REGULAR = "regular";
    const TYPE_DEFAULT = "default";
    const FORCED = "forced";
    public function getTypes()
    {
        return [REGULAR, TYPE_DEFAULT, FORCED];
    }
    public function getTypesAsOptions()
    {
        $options = [];
        foreach ($this->getTypes() as $type) {
            $options[] = ["value" => $type, "name" => \Core\Locale\LocaleService::t("streams.types." . $type)];
        }
        return $options;
    }
}

?>