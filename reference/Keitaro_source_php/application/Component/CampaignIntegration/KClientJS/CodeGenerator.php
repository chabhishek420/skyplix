<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\CampaignIntegration\KClientJS;

class CodeGenerator
{
    private $code = "\n    (function() {\n    var name = '{KTracking}';\n    if (!window.{KTracking}) {\n        window.{KTracking} = {\n            unique: {unique},\n            ttl: {ttl},\n            R_PATH: '{campaignUrl}',\n        };\n    }\n    const {configStorage} = localStorage.getItem('config');\n    if (typeof {configStorage} !== 'undefined' && {configStorage} !== null) {\n        var {config} = JSON.parse({configStorage});\n        var {now} = Math.round(+new Date()/1000);\n        if ({config}.created_at + window.{KTracking}.ttl < {now}) {\n            localStorage.removeItem('subId');\n            localStorage.removeItem('token');\n            localStorage.removeItem('config');\n        }\n    }\n    var {subId} = localStorage.getItem('subId');\n    var {token} = localStorage.getItem('token');\n    var {params} = '?return=js.client';\n        {params} += '&' + decodeURIComponent(window.location.search.replace('?', ''));\n        {params} += '&se_referrer=' + encodeURIComponent(document.referrer);\n        {params} += '&default_keyword=' + encodeURIComponent(document.title);\n        {params} += '&landing_url=' + encodeURIComponent(document.location.hostname + document.location.pathname);\n        {params} += '&name=' + encodeURIComponent(name);\n        {params} += '&host=' + encodeURIComponent(window.{KTracking}.R_PATH);\n    if (typeof {subId} !== 'undefined' && {subId} && window.{KTracking}.unique) {\n        {params} += '&sub_id=' + encodeURIComponent({subId});\n    }\n    if (typeof {token} !== 'undefined' && {token} && window.{KTracking}.unique) {\n        {params} += '&token=' + encodeURIComponent({token});\n    }\n    var a = document.createElement('script');\n        a.type = 'application/javascript';\n        a.src = window.{KTracking}.R_PATH + {params};\n    var s = document.getElementsByTagName('script')[0];\n    s.parentNode.insertBefore(a, s)\n    })();\n    ";
    const CLIENT_LOCATION_DEFAULT = NULL;
    public function getCode(KClientJSSettings $settings)
    {
        $secret = \Component\AffiliateNetworks\Repository\NetworkTemplatesRepository::instance()->getSecret();
        $urlPostback = $this->_createLinkPostback($secret);
        $replaces = ["{campaignUrl}" => $settings->getUrl(), "{campaignHost}" => $settings->getHost(), "{unique}" => $settings->getUnique(), "{ttl}" => $settings->getCookiesTTL(), "{postback_url}" => $urlPostback, "{subId}" => "_" . \Traffic\Tools\Tools::generateRandomString(16), "{token}" => "_" . \Traffic\Tools\Tools::generateRandomString(16), "{params}" => "_" . \Traffic\Tools\Tools::generateRandomString(16), "{KTracking}" => "_" . \Traffic\Tools\Tools::generateRandomString(16), "{configStorage}" => "_" . \Traffic\Tools\Tools::generateRandomString(16), "{config}" => "_" . \Traffic\Tools\Tools::generateRandomString(16), "{now}" => "_" . \Traffic\Tools\Tools::generateRandomString(16)];
        $code = $this->_replaceMacros($replaces, $this->code);
        if ($settings->getBase()) {
            return "<script src=\"data:text/javascript;base64," . base64_encode($code) . "\"></script>";
        }
        return "<script>" . $code . "</script>";
    }
    public function generateClientCode(\Traffic\Request\ServerRequest $request, $subId, $token, $content, $name)
    {
        $debug = $this->_checkDebugMode($request);
        $jsClient = file_get_contents(CLIENT_LOCATION_DEFAULT);
        $replaces = ["var keitaro=" => "var _" . \Traffic\Tools\Tools::generateRandomString(16) . "=", "{subid}" => $subId, "{token}" => $token, "debug:!1" => $debug ? "debug: true" : "debug: false", "KTracking" => $name];
        $jsClient = $this->_replaceMacros($replaces, $jsClient);
        $contentResult = $this->_prepareContent($request, $content);
        $jsClient = preg_replace("/\"{content}\"/", json_encode($contentResult), $jsClient);
        return $jsClient;
    }
    private function _createLinkPostback($secret)
    {
        return $secret . "/postback";
    }
    private function _replaceMacros($data, $content)
    {
        $result = $content;
        foreach ($data as $key => $value) {
            $result = preg_replace("/" . $key . "/", $value, $result);
        }
        return $result;
    }
    private function _checkDebugMode(\Traffic\Request\ServerRequest $request)
    {
        if ($request->hasParam("_new") && $request->getParam("_new") == 1) {
            return true;
        }
        return false;
    }
    private function _prepareContent(\Traffic\Request\ServerRequest $request, $content)
    {
        $result = $content;
        if ($content) {
            $result->body = $this->_updateBaseHref($request, $content->body);
            $result->body = base64_encode($result->body);
            return $result;
        }
        return "\"\"";
    }
    private function _updateBaseHref(\Traffic\Request\ServerRequest $request, $body)
    {
        $result = $body;
        if (!preg_match("/base\\s+href=\"(?:\\/\\/|http)/m", $body)) {
            $baseHref = $this->_getBaseHrefUrl($request);
            $result = str_replace("base href=\"/", "base href=\"" . $baseHref . "/", $result);
        }
        return $result;
    }
    private function _getBaseHrefUrl(\Traffic\Request\ServerRequest $request)
    {
        if ($request->hasParam("host")) {
            $host = parse_url($request->getParam("host"));
            return $host["scheme"] . "://" . $host["host"];
        }
        return $request->getUri()->getScheme() . "://" . $request->getUri()->getHost();
    }
}

?>