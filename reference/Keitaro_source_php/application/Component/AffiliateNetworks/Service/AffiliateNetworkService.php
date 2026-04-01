<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AffiliateNetworks\Service;

class AffiliateNetworkService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Traffic\Model\AffiliateNetwork::definition();
    }
}

?>