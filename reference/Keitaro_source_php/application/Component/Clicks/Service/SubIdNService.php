<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Service;

class SubIdNService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Clicks\Model\Ref\SubIdN::definition();
    }
    public function getOrCreateSubIdN($value)
    {
        $ref = \Component\Clicks\Repository\SubIdNRepository::instance()->findFirst("value = " . \Core\Db\Db::quote($value));
        if (empty($ref)) {
            $ref = $this->create(["value" => $value]);
        }
        return $ref;
    }
}

?>