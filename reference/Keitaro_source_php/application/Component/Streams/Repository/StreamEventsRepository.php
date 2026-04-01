<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Repository;

class StreamEventsRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Streams\Model\StreamEvent::definition();
    }
    public function last()
    {
        return \Core\Db\DataRepository::instance()->findLast($this->definition());
    }
}

?>