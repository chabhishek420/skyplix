<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\ConversionCapacity\Service;

class ConversionCapacityService extends \Traffic\Service\AbstractService
{
    public function apply(\Traffic\Model\Offer $offer, \Traffic\Model\Conversion $conversion)
    {
        \Component\Conversions\ConversionCapacity\Repository\ConversionCapacityRepository::instance()->getCurrentStorage()->store($offer, $conversion);
    }
    public function findAvailableOffer(\Traffic\Model\Offer $offer, \DateTime $currentDateTime = NULL, $previousChecks = [])
    {
        if (!$offer->isConversionCapEnabled()) {
            return $offer;
        }
        if (in_array($offer->getId(), $previousChecks)) {
            throw new \Component\Conversions\ConversionCapacity\Error\RecursionError("Alternative offer chain never ends: " . implode("→", $previousChecks));
        }
        if (empty($currentDateTime)) {
            $currentDateTime = new \DateTime();
        }
        if (!\Component\Conversions\ConversionCapacity\Repository\ConversionCapacityRepository::instance()->isCapReached($offer, $currentDateTime)) {
            return $offer;
        }
        if ($offer->get("alternative_offer_id")) {
            $previousChecks[] = $offer->getId();
            $offer = \Traffic\Repository\CachedOfferRepository::instance()->findCached($offer->get("alternative_offer_id"));
            return $this->findAvailableOffer($offer, $currentDateTime, $previousChecks);
        }
    }
}

?>