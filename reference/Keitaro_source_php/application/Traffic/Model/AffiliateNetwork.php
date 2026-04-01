<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class AffiliateNetwork extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_className = "AffiliateNetwork";
    protected static $_tableName = "affiliate_networks";
    protected static $_aclKey = "affiliate_networks";
    protected static $_cacheKey = "AFFILIATE_NETWORK";
    protected static $_entityName = "affiliate_network";
    const PULL_API_TIMEOUT_KEY = "time_to_query";
    const PULL_API_URL_KEY = "url";
    const PULL_API_FORMAT_KEY = "format";
    const PULL_API_MACRO_KEY = "required_macro";
    public static function serializer()
    {
        return new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer(true);
    }
    public static function validator()
    {
        return new \Component\AffiliateNetworks\Validator\AffiliateNetworkValidator();
    }
    public static function reportDefinition()
    {
        return new \Component\AffiliateNetworks\Grid\AffiliateNetworkGridDefinition();
    }
    public static function repository()
    {
        return \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance();
    }
    public static function service()
    {
        return \Component\AffiliateNetworks\Service\AffiliateNetworkService::instance();
    }
    public function getName()
    {
        return $this->get("name");
    }
    public function getOfferParam()
    {
        return $this->get("offer_param");
    }
    public function getPullApiMacros()
    {
        $params = $this->getPullApiOptions();
        if (empty($params)) {
            return NULL;
        }
        return $params["required_macro"];
    }
    public function getPostbackUrl()
    {
        return $this->get("postback_url");
    }
}

?>