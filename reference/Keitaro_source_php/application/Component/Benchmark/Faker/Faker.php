<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark\Faker;

class Faker
{
    private static $_cnt = 0;
    public function rawClick($datetime = NULL)
    {
        $params = $this->getRawClickParams($datetime);
        return new \Traffic\RawClick($params);
    }
    public function getRawClickParams($datetime = NULL)
    {
        if (!$datetime) {
            $datetime = new \DateTime();
        }
        $ip = $this->ip();
        $params = ["ip" => ip2long($ip), "ip_string" => $ip, "keyword" => $this->keyword(), "source" => $this->source(), "ua" => $this->userAgent(), "language" => "en", "referrer" => $this->referrer(), "se_referrer" => $this->referrer(), "datetime" => $datetime, "ad_campaign_id" => "ad" . rand(0, 100), "creative_id" => "banner" . rand(0, 100), "external_id" => "click" . ++self::$_cnt, "sub_id_1" => "x" . rand(0, 10000), "sub_id_2" => "x" . rand(0, 10000), "sub_id_3" => "x" . rand(0, 10000)];
        return $params;
    }
    public function ip()
    {
        return FakerIp::get();
    }
    public function keyword()
    {
        return FakerKeyword::get();
    }
    public function source()
    {
        return FakerSource::get();
    }
    public function userAgent()
    {
        return FakerUa::get();
    }
    public function referrer()
    {
        return FakerReferrer::get();
    }
    public function campaign()
    {
        return \Component\Campaigns\Service\CampaignService::instance()->create(["alias" => md5(rand(1000000, 99999999)), "name" => "test", "cookies_ttl" => 24, "type" => \Traffic\Model\Campaign::TYPE_POSITION, "token" => "x" . ++self::$_cnt]);
    }
    public function stream(\Traffic\Model\Campaign $campaign)
    {
        return \Component\Streams\Service\StreamService::instance()->create(["type" => \Traffic\Model\Stream::TYPE_REGULAR, "schema" => \Traffic\Model\BaseStream::ACTION, "campaign_id" => $campaign->getId(), "action_type" => "http"]);
    }
}

?>