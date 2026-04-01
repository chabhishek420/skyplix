<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Serializer;

class StreamOfferAssociationSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    private $_withOfferCapacity = NULL;
    public function __construct($withOfferCapacity = false)
    {
        $this->_withOfferCapacity = $withOfferCapacity;
    }
    public function extra($obj, $data)
    {
        $data = $this->_flatTimestamps($data);
        if ($this->_withOfferCapacity) {
            $offer = \Component\Streams\Repository\PreloadedResourceRepository::instance()->getOffer($data["offer_id"]);
            if ($offer && $offer->isConversionCapEnabled()) {
                $data["daily_cap"] = $offer->getDailyCap();
                $data["conversions"] = \Component\Conversions\ConversionCapacity\Repository\ConversionCapacityRepository::instance()->currentValueForOffer($offer);
            }
        }
        return $data;
    }
}

?>