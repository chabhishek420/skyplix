<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\AppsFlyer\Api;

class Connect
{
    private $_client = NULL;
    const API_URL = "https://hq.appsflyer.com/export/{app_name}/installs_report/v5";
    const APP_NAME = "{app_name}";
    const TIMEOUT = 10;
    public function __construct()
    {
        $opts = [];
        $opts["headers"] = ["Accept" => "text/csv"];
        $this->_client = new \GuzzleHttp\Client($opts);
    }
    public function getAppsFlyerApi($app_name, $data)
    {
        $url = str_replace(APP_NAME, $app_name, API_URL);
        $uri = new \GuzzleHttp\Psr7\Uri($url);
        $uri = $uri->withQuery(http_build_query($data));
        $request = new \GuzzleHttp\Psr7\Request("GET", $uri);
        $result = $this->makeRequest($request);
        return $result->getBody()->getContents();
    }
    public function makeRequest(\GuzzleHttp\Psr7\Request $request)
    {
        return $this->_client->send($request, [\GuzzleHttp\RequestOptions::TIMEOUT => TIMEOUT, \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => TIMEOUT]);
    }
}

?>