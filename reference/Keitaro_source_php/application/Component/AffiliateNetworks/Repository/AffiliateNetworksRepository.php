<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AffiliateNetworks\Repository;

class AffiliateNetworksRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\Model\AffiliateNetwork::definition();
    }
    public function listAsOptions($models)
    {
        return \Core\Entity\ListOptions\Builder::build($this->definition(), $models, ["template" => "template_name"]);
    }
}

?>