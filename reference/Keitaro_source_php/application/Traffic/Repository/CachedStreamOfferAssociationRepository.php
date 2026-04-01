<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class CachedStreamOfferAssociationRepository extends AbstractBaseRepository
{
    public function getCachedByStream(\Traffic\Model\BaseStream $stream, $useFallbackStrategy = true)
    {
        return \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\Model\StreamOfferAssociation::entityName(), ["stream_id" => $stream->getId()], $useFallbackStrategy);
    }
    public function cachedStreamHasOfferId(\Traffic\Model\BaseStream $stream, $offerId)
    {
        $offerAssocs = $this->getCachedByStream($stream, true);
        $found = false;
        foreach ($offerAssocs as $offerAssoc) {
            if ($offerAssoc->getOfferId() == $offerId) {
                $found = true;
                return $found;
            }
        }
    }
    public function hasCachedLandings(\Traffic\Model\BaseStream $stream)
    {
        return \Traffic\CachedData\Repository\CachedDataRepository::instance()->exists(\Traffic\Model\StreamLandingAssociation::entityName(), ["stream_id" => $stream->getId()]);
    }
    public function hasCachedOffers(\Traffic\Model\BaseStream $stream)
    {
        return \Traffic\CachedData\Repository\CachedDataRepository::instance()->exists(\Traffic\Model\StreamOfferAssociation::entityName(), ["stream_id" => $stream->getId()]);
    }
}

?>