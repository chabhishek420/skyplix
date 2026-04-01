<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Repository;

class StreamFilterRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\Model\StreamFilter::definition();
    }
    public function allByStream(\Traffic\Model\BaseStream $stream)
    {
        return $this->all("stream_id = " . (int) $stream->getId(), "id");
    }
}

?>