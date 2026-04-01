<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\HitLimit\Storage;

final class StorageInterface
{
    public abstract function store(\Traffic\Model\BaseStream $stream, \DateTime $currentDateTime);
    public abstract function perHour(\Traffic\Model\BaseStream $stream, \DateTime $currentDateTime);
    public abstract function perDay(\Traffic\Model\BaseStream $stream, \DateTime $date);
    public abstract function total(\Traffic\Model\BaseStream $stream);
    public abstract function prune(\DateTime $currentDateTime);
}

?>