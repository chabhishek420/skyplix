<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AffiliateNetworks\Serializer;

class AffiliateNetworkSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    protected $_extended = NULL;
    public function __construct($extended = false)
    {
        return $this->_extended = $extended;
    }
    public function extra($obj, $data)
    {
        if ($this->_extended) {
            $data["offers"] = \Component\Offers\Repository\OfferRepository::instance()->countActive("affiliate_network_id = " . $data["id"]);
        }
        $data = $this->_flatTimestamps($data);
        return $data;
    }
}

?>