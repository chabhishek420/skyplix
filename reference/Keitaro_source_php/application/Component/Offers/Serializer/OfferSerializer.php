<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Serializer;

class OfferSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    private $_withGroupName = NULL;
    public function __construct($withGroupName = false)
    {
        $this->_withGroupName = $withGroupName;
    }
    public function extra($obj, $data)
    {
        $data = $this->_flatTimestamps($data);
        if (!$this->_withGroupName) {
            unset($data["group"]);
            unset($data["affiliate_network"]);
        }
        if (key_exists("affiliate_network_id", $data) && is_null($data["affiliate_network_id"])) {
            $data["affiliate_network_id"] = 0;
        }
        if ($obj->getActionType() == "local_file") {
            $data = \Component\Offers\Service\OfferService::instance()->addPreviewData($obj, $data);
        }
        if ($obj->isConversionCapEnabled()) {
            $data["conversion_cap"] = \Component\Conversions\ConversionCapacity\Repository\ConversionCapacityRepository::instance()->currentValueForOffer($obj);
        }
        return $data;
    }
}

?>