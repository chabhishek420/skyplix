<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Repository;

class SubIdNRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Clicks\Model\Ref\SubIdN::definition();
    }
}

?>