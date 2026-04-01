<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class HideClickDetect extends \Core\Filter\AbstractFilter
{
    const BLACK = "black";
    const WHITE = "white";
    const HIDE_CLICK_URL = "http://api.hideapi.xyz/basic";
    public function getGroup()
    {
        return "filters.groups.geo";
    }
    public function isPass($isPass, \Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        if (!\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::HIDE_CLICK_KEY)) {
            throw new \Exception("'HideClick settings error");
        }
        $request = $this->getServerRequest();
        $headers = [];
        if ($request->getParam("original_headers")) {
            $headers = $request->getParam("original_headers");
        } else {
            foreach ($request->getHeaders() as $header => $values) {
                foreach ($values as $value) {
                    $headers[$header] = $value;
                }
            }
        }
        $params = [];
        $params["ip"] = $rawClick->getIpString();
        $params["headers"] = $headers;
        $tmpParsedURL = parse_url($request->getUri());
        $tmpParsedURL["path"] ? exit : "" . (empty($tmpParsedURL["query"]) ? "" : "?" . $tmpParsedURL["query"]);
    }
    public function getModes()
    {
        return [BLACK => "Black", WHITE => "White"];
    }
    public function getTemplate()
    {
        if (\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::HIDE_CLICK_KEY)) {
            return self::getTemplate();
        }
        return \Core\Locale\LocaleService::t("filters.no_hide_click");
    }
    private function _sendRequest($_sendRequest, $params, $payload)
    {
        $key = \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::HIDE_CLICK_KEY);
        $payload["paranoia"] ? exit : NULL;
    }
}

?>