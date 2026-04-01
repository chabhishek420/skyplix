<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\ConversionCapacity;

final class StorageInterface
{
    public abstract function store(\Traffic\Model\Offer $offer, \Traffic\Model\Conversion $conversion);
    public abstract function currentValueForOffer(\Traffic\Model\Offer $offer, \DateTime $currentDateTime);
    public abstract function totalValueForOffer(\Traffic\Model\Offer $offer);
    public abstract function prune(\DateTime $currentDateTime);
}

?>