<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic;

final class RawClickInterface
{
    public abstract function get($key);
    public abstract function getData();
    public abstract function getParentCampaignId();
    public abstract function getLandingUrl();
    public abstract function getAffiliateNetworkId();
    public abstract function isLead();
    public abstract function isSale();
    public abstract function isRejected();
    public abstract function getCost();
    public abstract function getProfit();
    public abstract function getLandingId();
    public abstract function getOfferId();
    public abstract function getStreamId();
    public abstract function getRevenue();
    public abstract function getIp();
    public abstract function getIpString();
    public abstract function getCountry();
    public abstract function getRegion();
    public abstract function getCity();
    public abstract function getLanguage();
    public abstract function getBrowser();
    public abstract function getBrowserVersion();
    public abstract function getIsp();
    public abstract function getConnectionType();
    public abstract function getOperator();
    public abstract function getOs();
    public abstract function getOsVersion();
    public abstract function getSearchEngine();
    public abstract function getDateTime();
    public abstract function getKeyword();
    public abstract function getSource();
    public abstract function getXRequestedWith();
    public abstract function getUserAgent();
    public abstract function isMobile();
    public abstract function isUsingProxy();
    public abstract function isGeoResolved();
    public abstract function isDeviceResolved();
    public abstract function isIspResolved();
    public abstract function isProcessed();
    public abstract function getReferer();
    public abstract function getReferrer();
    public abstract function getParentSubId();
    public abstract function getSubId();
    public abstract function getSubIdN($num);
    public abstract function getExternalId();
    public abstract function getAdCampaignId();
    public abstract function getCreativeId();
    public abstract function getExtraParam($num);
    public abstract function getDeviceModel();
    public abstract function getDeviceBrand();
    public abstract function getDeviceType();
    public abstract function getDestination();
    public abstract function getTsId();
    public abstract function isUniqueCampaign();
    public abstract function isUniqueGlobal();
    public abstract function isUniqueStream();
    public abstract function isBot();
    public abstract function getVisitorCode();
    public abstract function getCampaignId();
    public abstract function getToken();
}

?>