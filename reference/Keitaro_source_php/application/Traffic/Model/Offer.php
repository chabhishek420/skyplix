<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class Offer extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    use Mixin\StateMethodsTrait;
    use Mixin\StreamActionableMethodsTrait;
    protected static $_fields = NULL;
    protected static $_className = "Offer";
    protected static $_tableName = "offers";
    protected static $_cacheKey = "OFFER";
    protected static $_aclKey = "offers";
    protected static $_entityName = "offer";
    const PAYOUT_TYPE_CPC = "CPC";
    const PAYOUT_TYPE_CPA = "CPA";
    public static function validator()
    {
        return new \Component\Offers\Validator\OfferValidator();
    }
    public static function getValidPayoutTypes()
    {
        return [PAYOUT_TYPE_CPA, PAYOUT_TYPE_CPC];
    }
    public static function serializer()
    {
        return new \Component\Offers\Serializer\OfferSerializer(true);
    }
    public static function repository()
    {
        return \Component\Offers\Repository\OfferRepository::instance();
    }
    public static function service()
    {
        return \Component\Offers\Service\OfferService::instance();
    }
    public static function reportDefinition()
    {
        return new \Component\Offers\Grid\OfferGridDefinition();
    }
    public function getPayoutValue()
    {
        return $this->get("payout_value");
    }
    public function getPayoutCurrency()
    {
        return $this->get("payout_currency");
    }
    public function isPayoutAuto()
    {
        return $this->get("payout_auto");
    }
    public function isCPC()
    {
        return $this->get("payout_type") == PAYOUT_TYPE_CPC;
    }
    public function getAffiliateNetworkId()
    {
        return $this->get("affiliate_network_id");
    }
    public function isConversionCapEnabled()
    {
        return $this->get("conversion_cap_enabled");
    }
    public function getDailyCap()
    {
        return $this->get("daily_cap");
    }
}

?>