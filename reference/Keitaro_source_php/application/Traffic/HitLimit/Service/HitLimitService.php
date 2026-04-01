<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\HitLimit\Service;

class HitLimitService extends \Traffic\Service\AbstractService
{
    private $_storage = NULL;
    public function setStorage(\Traffic\HitLimit\Storage\StorageInterface $storage)
    {
        return $this->_storage = $storage;
    }
    public function store(\Traffic\Model\BaseStream $stream, \DateTime $date)
    {
        return $this->_storage->store($stream, $date);
    }
    public function perHour(\Traffic\Model\BaseStream $stream, \DateTime $date)
    {
        return $this->_storage->perHour($stream, $date);
    }
    public function perDay(\Traffic\Model\BaseStream $stream, \DateTime $date)
    {
        return $this->_storage->perDay($stream, $date);
    }
    public function total(\Traffic\Model\BaseStream $stream)
    {
        return $this->_storage->total($stream);
    }
    public function prune(\DateTime $date)
    {
        return $this->_storage->prune($date);
    }
    public function getState(\Traffic\Model\BaseStream $stream, \DateTime $date)
    {
        return ["per_hour" => $this->perHour($stream, $date), "per_day" => $this->perDay($stream, $date), "total" => $this->total($stream)];
    }
}

?>