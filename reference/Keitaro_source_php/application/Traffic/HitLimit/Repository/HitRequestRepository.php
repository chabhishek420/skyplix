<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\HitLimit\Repository;

class HitRequestRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\HitLimit\Model\HitRequest::definition();
    }
}

?>