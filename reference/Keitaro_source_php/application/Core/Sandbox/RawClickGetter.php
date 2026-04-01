<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

class RawClickGetter implements Traffic\RawClickInterface
{
    private $_data = [];
    const DEVICE_TABLET = "tablet";
    const DEVICE_MOBILE = "mobile";
    public function __construct($data = NULL)
    {
        if (isset($data) && is_array($data)) {
            $this->_data = $data;
        }
    }
    public function get($key)
    {
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return "";
    }
    public function getData()
    {
        return $this->_data;
    }
    private function _checkInFields($key)
    {
        if ($key == "datetime" && is_string($this->_data["datetime"])) {
            $this->_data["datetime"] = new DateTime($this->_data["datetime"]);
        }
    }
    public function getParentCampaignId()
    {
        return $this->get("parent_campaign_id");
    }
    public function getLandingUrl()
    {
        return $this->get("landing_url");
    }
    public function getAffiliateNetworkId()
    {
        return $this->get("affiliate_network_id");
    }
    public function isLead()
    {
        return $this->get("is_lead");
    }
    public function isSale()
    {
        return $this->get("is_sale");
    }
    public function isRejected()
    {
        return $this->get("is_rejected");
    }
    public function getCost()
    {
        return $this->get("cost");
    }
    public function getProfit()
    {
        return $this->getRevenue() - $this->getCost();
    }
    public function getLandingId()
    {
        return $this->get("landing_id");
    }
    public function getOfferId()
    {
        return $this->get("offer_id");
    }
    public function getStreamId()
    {
        return $this->get("stream_id");
    }
    public function getRevenue()
    {
        return (int) $this->get("lead_revenue") + (int) $this->get("sale_revenue") + (int) $this->get("rejected_revenue");
    }
    public function getIp()
    {
        return $this->get("ip");
    }
    public function getIpString()
    {
        if (!$this->get("ip_string")) {
            $this->set("ip_string", long2ip((int) $this->getIp()));
        }
        return $this->get("ip_string");
    }
    public function getCountry()
    {
        return $this->get("country");
    }
    public function getRegion()
    {
        return $this->get("region");
    }
    public function getCity()
    {
        return $this->get("city");
    }
    public function getLanguage()
    {
        return $this->get("language");
    }
    public function getBrowser()
    {
        return $this->get("browser");
    }
    public function getBrowserVersion()
    {
        return $this->get("browser_version");
    }
    public function getIsp()
    {
        return $this->get("isp");
    }
    public function getConnectionType()
    {
        return $this->get("connection_type");
    }
    public function getOperator()
    {
        return $this->get("operator");
    }
    public function getOs()
    {
        return $this->get("os");
    }
    public function getOsVersion()
    {
        return $this->get("os_version");
    }
    public function getSearchEngine()
    {
        return $this->get("search_engine");
    }
    public function getDateTime()
    {
        return $this->get("datetime");
    }
    public function getKeyword()
    {
        return $this->get("keyword");
    }
    public function getSource()
    {
        return $this->get("source");
    }
    public function getXRequestedWith()
    {
        return $this->get("x_requested_with");
    }
    public function getUserAgent()
    {
        return $this->get("user_agent");
    }
    public function isMobile()
    {
        return in_array($this->getDeviceType(), ["tablet", "mobile"]);
    }
    public function isUsingProxy()
    {
        return $this->get("is_using_proxy");
    }
    public function isGeoResolved()
    {
        return $this->get("is_geo_resolved");
    }
    public function isDeviceResolved()
    {
        return $this->get("is_device_resolved");
    }
    public function isIspResolved()
    {
        return $this->get("is_isp_resolved");
    }
    public function isProcessed()
    {
        return $this->get("processed");
    }
    public function getReferer()
    {
        return $this->getReferrer();
    }
    public function getReferrer()
    {
        return $this->get("referrer");
    }
    public function getParentSubId()
    {
        return $this->get("parent_sub_id");
    }
    public function getSubId()
    {
        return $this->get("sub_id");
    }
    public function getSubIdN($num = NULL)
    {
        if (!isset($num)) {
            return $this->get("sub_id");
        }
        return $this->get("sub_id_" . $num);
    }
    public function getExternalId()
    {
        return $this->get("external_id");
    }
    public function getAdCampaignId()
    {
        return $this->get("ad_campaign_id");
    }
    public function getCreativeId()
    {
        return $this->get("creative_id");
    }
    public function getExtraParam($num)
    {
        return $this->get("extra_param_" . $num);
    }
    public function getDeviceModel()
    {
        return $this->get("device_model");
    }
    public function getDeviceBrand()
    {
        return $this->get("device_brand");
    }
    public function getDeviceType()
    {
        return $this->get("device_type");
    }
    public function getDestination()
    {
        return $this->get("destination");
    }
    public function getTsId()
    {
        return $this->get("ts_id");
    }
    public function isUniqueCampaign()
    {
        return $this->get("is_unique_campaign");
    }
    public function isUniqueGlobal()
    {
        return $this->get("is_unique_global");
    }
    public function isUniqueStream()
    {
        return $this->get("is_unique_stream");
    }
    public function isBot()
    {
        return $this->get("is_bot");
    }
    public function getVisitorCode()
    {
        return $this->get("visitor_code");
    }
    public function getCampaignId()
    {
        return $this->get("campaign_id");
    }
    public function getToken()
    {
        return $this->get("token");
    }
}

?>