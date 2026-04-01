<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Facebook\Api;

class Connect
{
    private $_client = NULL;
    const API_URL = "https://graph.facebook.com/v7.0";
    const TIMEOUT = 10;
    const ADSET_LIMIT = 1000;
    public function __construct($useProxy = false, $proxyData = [])
    {
        $opts = [];
        if ($useProxy) {
            $proxy = new Proxy($proxyData);
            $opts["proxy"] = $proxy->proxyString();
            if ($proxy->ua()) {
                $opts["headers"] = ["User-Agent" => $proxy->ua()];
            }
        }
        $this->_client = new \GuzzleHttp\Client($opts);
    }
    public function getFacebookApi($path, $data)
    {
        $uri = new \Laminas\Diactoros\Uri(API_URL . $path);
        $uri = $uri->withQuery(http_build_query($data));
        $request = new \GuzzleHttp\Psr7\Request("GET", $uri);
        $result = $this->makeRequest($request);
        return json_decode($result->getBody(), true);
    }
    public function makeRequest(\GuzzleHttp\Psr7\Request $request)
    {
        return $this->_client->send($request, [\GuzzleHttp\RequestOptions::TIMEOUT => TIMEOUT, \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => TIMEOUT]);
    }
    public function checkToken($token)
    {
        try {
            $me = $this->getFacebookApi("/me", ["access_token" => $token]);
            if (isset($me["error"])) {
                throw new \Component\ThirdPartyIntegration\Exception\FbConnectionError($me["error"]);
            }
            return true;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = $e->getResponse()->getBody()->getContents();
            $data = json_decode($body);
            throw new \Component\ThirdPartyIntegration\Exception\FbConnectionError($data->error->message);
        }
    }
    public function getCabinetInfo($cabinetId, $token)
    {
        try {
            $info = $this->getFacebookApi("/act_" . $cabinetId, ["access_token" => $token, "fields" => "currency,timezone_name,timezone_offset_hours_utc"]);
            if (isset($info["error"])) {
                throw new \Component\ThirdPartyIntegration\Exception\FbConnectionError($info["error"]);
            }
            return $info;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = $e->getResponse()->getBody()->getContents();
            $data = json_decode($body);
            throw new \Component\ThirdPartyIntegration\Exception\FbConnectionError($data->error->message);
        }
    }
    public function getAdsets($cabinetId, $token)
    {
        try {
            $info = $this->getFacebookApi("/act_" . $cabinetId . "/adsets", ["access_token" => $token, "limit" => ADSET_LIMIT]);
            if (isset($info["error"])) {
                throw new \Component\ThirdPartyIntegration\Exception\FbConnectionError($info["error"]);
            }
            return $info;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = $e->getResponse()->getBody()->getContents();
            $data = json_decode($body);
            throw new \Component\ThirdPartyIntegration\Exception\FbConnectionError($data->error->message);
        }
    }
    public function getInsights($adsetId, $timezone, $token, $first = false)
    {
        $diffTime = 432000;
        if ($first) {
            $diffTime = 2592000;
        }
        $since = gmdate("Y-m-d", time() - $diffTime);
        try {
            $insights = $this->getFacebookApi("/" . $adsetId . "/insights", ["access_token" => $token, "fields" => "spend", "time_range" => ["since" => $since, "until" => gmdate("Y-m-d", time() + 3600 * ($timezone + date("I")))], "time_increment" => "1"]);
            if (isset($insights["error"])) {
                throw new \Component\ThirdPartyIntegration\Exception\FbConnectionError($insights["error"]);
            }
            return $insights;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = $e->getResponse()->getBody()->getContents();
            $data = json_decode($body);
            throw new \Component\ThirdPartyIntegration\Exception\FbConnectionError($data->error->message);
        }
    }
}

?>