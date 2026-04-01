<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\Service;

class ConversionService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Traffic\Model\Conversion::definition();
    }
    public function hasSubId15()
    {
        return $this->isColumnExists(\Traffic\Repository\ParameterRepository::SUB_ID_15_ID);
    }
    public function hasXRequestedWith()
    {
        return $this->isColumnExists(\Traffic\Repository\ParameterRepository::X_REQUESTED_WITH_ID);
    }
    public function hasAffiliateNetworkId()
    {
        return $this->isColumnExists(\Traffic\Repository\ParameterRepository::AFFILIATE_NETWORK_ID);
    }
    public function isColumnExists($name)
    {
        return \Traffic\Repository\CachedSettingsRepository::instance()->get("conversions_2_" . $name . "_exists") || \Traffic\Repository\CachedSettingsRepository::instance()->get("conversions_" . $name . "_exists");
    }
}

?>