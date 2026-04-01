<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class ImkloDetect extends \Core\Filter\AbstractFilter
{
    const BLACK = "black";
    const WHITE = "white";
    public function getGroup()
    {
        return "filters.groups.geo";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        if (!\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::IMKLO_API_URL)) {
            throw new \Exception("'IMKLO settings error");
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
        $params = $this->_prepareParams($rawClick, $request, $headers);
        $white = $this->_sendRequest($params);
        return $filter->getMode() == BLACK && !$white || $filter->getMode() == WHITE && $white;
    }
    public function getModes()
    {
        return [BLACK => "Black", WHITE => "White"];
    }
    public function getTemplate()
    {
        if (\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::IMKLO_API_URL)) {
            return self::getTemplate();
        }
        return \Core\Locale\LocaleService::t("filters.no_imklo");
    }
    private function _sendRequest($params)
    {
        $url = \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::IMKLO_API_URL);
        if (!strstr($url, "http")) {
            $url = "http://" . $url;
        }
        if (!strstr($url, "/api")) {
            $url = $url . "/api";
        }
        if (!strstr($url, "check_ip")) {
            $url = $url . "/check_ip";
        }
        try {
            $response = \Traffic\Http\Service\HttpService::instance()->post($url, [], [\GuzzleHttp\RequestOptions::FORM_PARAMS => $params, \GuzzleHttp\RequestOptions::TIMEOUT => 5]);
            $content = $this->_removeBOM((int) $response->getBody());
            $json = json_decode($content);
            $this->getLogger()->add("IMKLO: " . $response->getBody());
            if (!empty($json->errors)) {
                $this->getLogger()->add("IMKLO errors: " . implode(",", $json->errors));
            }
            return !$json || !empty($json->white_link) || $json->result == 0;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error("IMKLO DETECT ERROR: " . $e->getMessage());
            return true;
        }
    }
    private function _removeBOM($data)
    {
        if (0 === strpos(bin2hex($data), "efbbbf")) {
            return substr($data, 3);
        }
        return $data;
    }
    private function _prepareParams($_prepareParams, \Traffic\RawClick $rawClick, \Traffic\Request\ServerRequest $request, $headers)
    {
        $params = [];
        $params["ip"] = $rawClick->getIpString();
        $params["ip"] = $rawClick->getIpString();
        if ($request->getParam("original_host")) {
            $params["domain"] = $request->getParam("original_host");
        } else {
            $params["domain"] = $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_HOST);
        }
        $params["referer"] = $request->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_REFERER);
        $params["user_agent"] = $rawClick->getUserAgent();
        $params["url"] = $request->getServerParam("REQUEST_URI");
        $params["headers"] = json_encode($headers);
        $params["get"] = json_encode($request->getQueryParams());
        if ($request->hasParam("kversion") && $request->hasParam("uri")) {
            $url = parse_url($request->getParam("uri"));
            $params["url"] = $url["path"];
            if (!empty($url["query"])) {
                parse_str($url["query"], $query);
                $params["get"] = json_encode($query);
            }
        }
        return $params;
    }
}

?>