<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class Campaign extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    use Mixin\StateMethodsTrait;
    protected static $_fields = NULL;
    protected static $_tableName = "campaigns";
    protected static $_cacheKey = "CAMPAIGNS";
    protected static $_aclKey = "campaigns";
    protected static $_entityName = "campaign";
    const BIND_VISITOR_STREAM_LANDING_OFFER = "slo";
    const BIND_VISITOR_STREAM = "s";
    const COST_TYPE_CPM = "CPM";
    const COST_TYPE_CPC = "CPC";
    const COST_TYPE_CPUC = "CPUC";
    const COST_TYPE_REV_SHARE = "RevShare";
    const COST_TYPE_CPA = "CPA";
    const UNIQUENESS_METHOD_COOKIE_AND_IP = "cookie_and_ip";
    const UNIQUENESS_METHOD_IP = "ip";
    const BIND_VISITOR_STREAM_LANDING = "sl";
    const COST_TYPE_CPS = "CPS";
    const UNIQUENESS_METHOD_COOKIE = "cookie";
    const TYPE_POSITION = "position";
    const TYPE_WEIGHT = "weight";
    const UNIQUE_CHECK_BY_IP_UA = "ip_ua";
    const UNIQUE_CHECK_BY_IP = "ip";
    const DEFAULT_COOKIES_TTL = 24;
    const MIN_COOKIES_TTL = 1;
    const MAX_COOKIES_TTL = 8760;
    public static function validator()
    {
        return new \Component\Campaigns\Validator\CampaignValidator();
    }
    public function isUniquenessUseCookies()
    {
        return $this->get("uniqueness_use_cookies");
    }
    public static function reportDefinition()
    {
        return new \Component\Campaigns\Grid\CampaignGridDefinition();
    }
    public static function repository()
    {
        return \Component\Campaigns\Repository\CampaignRepository::instance();
    }
    public static function service()
    {
        return \Component\Campaigns\Service\CampaignService::instance();
    }
    public static function serializer()
    {
        return new \Component\Campaigns\Serializer\CampaignSerializer(true);
    }
    public function getState()
    {
        return $this->get("state");
    }
    public function getPosition()
    {
        return $this->get("position");
    }
    public function getGroupId()
    {
        return $this->get("group_id");
    }
    public function isTypePosition()
    {
        return $this->get("type") === TYPE_POSITION;
    }
    public function isWeightPosition()
    {
        return $this->get("type") === TYPE_WEIGHT;
    }
    public function isDisabled()
    {
        return $this->get("state") === \Core\Entity\State::DISABLED;
    }
    public function isBindVisitorsEnabled()
    {
        $bind = $this->get("bind_visitors");
        return $this->isWeightPosition() && !empty($bind);
    }
    public function isBindVisitorsLandingEnabled()
    {
        $bind = $this->get("bind_visitors");
        return $this->isWeightPosition() && 2 <= strlen($bind);
    }
    public function isBindVisitorsOfferEnabled()
    {
        $bind = $this->get("bind_visitors");
        return $this->isWeightPosition() && 3 <= strlen($bind);
    }
    public function isCostAuto()
    {
        return $this->get("cost_auto");
    }
    public function getMode()
    {
        return $this->get("mode");
    }
    public function setActionPayload($value)
    {
        $this->set("action_payload", $value);
        return $this;
    }
    public function setPosition($value)
    {
        $this->set("position", $value);
        return $this;
    }
    public function getCampaignType()
    {
        return $this->get("type");
    }
    public function getName()
    {
        return $this->get("name");
    }
    public function getUniquenessMethod()
    {
        return $this->get("uniqueness_method");
    }
    public function isUniqueByIpUa()
    {
        return $this->getUniquenessMethod() != UNIQUE_CHECK_BY_IP;
    }
    public function isUniqueByIp()
    {
        return $this->getUniquenessMethod() == UNIQUE_CHECK_BY_IP;
    }
    public function getCookiesTtl()
    {
        return (int) $this->get("cookies_ttl");
    }
    public function getActionType()
    {
        return $this->get("action_type");
    }
    public function getActionPayload()
    {
        return $this->get("action_payload");
    }
    public function getAlias()
    {
        return $this->get("alias");
    }
    public function getType()
    {
        return $this->get("type");
    }
    public function getCostType()
    {
        return $this->get("cost_type");
    }
    public function getCostValue()
    {
        return $this->get("cost_value");
    }
    public function getCostCurrency()
    {
        return $this->get("cost_currency");
    }
    public function getTrafficSourceId()
    {
        return $this->get("traffic_source_id");
    }
    public function getToken()
    {
        return $this->get("token");
    }
    public function isCostPerUnique()
    {
        return in_array($this->get("cost_type"), [COST_TYPE_CPUC, "CPUV"]);
    }
    public function isCostPerThousand()
    {
        return $this->get("cost_type") == COST_TYPE_CPM;
    }
    public function isCostPerAcquisition()
    {
        return $this->get("cost_type") == COST_TYPE_CPA;
    }
    public function isCostPerSale()
    {
        return $this->get("cost_type") == COST_TYPE_CPS;
    }
    public function isCostPerClick()
    {
        return in_array($this->get("cost_type"), [COST_TYPE_CPC, "CPV"]);
    }
    public function isCostRevShare()
    {
        return $this->get("cost_type") == COST_TYPE_REV_SHARE;
    }
    public function getParameters()
    {
        return $this->get("parameters");
    }
    public function getTrafficLoss()
    {
        return $this->get("traffic_loss");
    }
}

?>