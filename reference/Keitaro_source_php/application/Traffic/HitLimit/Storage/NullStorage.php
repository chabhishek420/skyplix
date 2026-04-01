<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\HitLimit\Storage;

class NullStorage implements StorageInterface
{
    public function store(\Traffic\Model\BaseStream $stream, \DateTime $currentDateTime)
    {
    }
    public function perHour(\Traffic\Model\BaseStream $stream, \DateTime $currentDateTime)
    {
        return 0;
    }
    public function perDay(\Traffic\Model\BaseStream $stream, \DateTime $date)
    {
        return 0;
    }
    public function total(\Traffic\Model\BaseStream $stream)
    {
        return 0;
    }
    public function prune(\DateTime $currentDateTime)
    {
    }
}

?>