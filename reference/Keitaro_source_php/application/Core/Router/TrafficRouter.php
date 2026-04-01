<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Router;

class TrafficRouter
{
    private $_routes = [];
    const PARAM_VERSION = "version";
    const PARAM_KEY = "k_router_key";
    const PARAM_CAMPAIGN = "k_router_campaign";
    public function __construct()
    {
        $this->_routes = [["pattern" => "/admin_api\\/(v[0-9]+)/i", "context" => "Admin\\Context\\AdminApiContext", "param" => PARAM_VERSION], ["pattern" => "/\\/([a-z0-9\\-_]+)\\/postback/i", "context" => "Traffic\\Context\\PostbackContext", "param" => PARAM_KEY], ["test" => function (\Traffic\Request\ServerRequest $request) {
            $keys = array_keys($request->getQueryParams());
            $first = array_shift($keys);
            if ($first === "postback" || $request->getParam("postback")) {
                return $request->hasParam("key") ? $request->getParam("key") : true;
            }
            return false;
        }, "context" => "Traffic\\Context\\PostbackContext", "param" => PARAM_KEY], ["test" => function (\Traffic\Request\ServerRequest $request) {
            return $request->getParam("_ping") === "domain";
        }, "context" => "Traffic\\Context\\PingDomainContext"], ["test" => function (\Traffic\Request\ServerRequest $request) {
            return $request->getParam("_ping") === "license";
        }, "context" => "Admin\\Context\\RefreshLicenseContext"], ["test" => function (\Traffic\Request\ServerRequest $request) {
            return $request->getParam("_update_tokens");
        }, "context" => "Traffic\\Context\\UpdateTokensContext"], ["pattern" => "/click_api\\/v([0-9])+\\/?/i", "context" => "Traffic\\Context\\ClickApiContext", "param" => PARAM_VERSION], ["pattern" => "/[\\/]*api\\.php\$/", "context" => "Traffic\\Context\\ClickApiContext"], ["test" => function (\Traffic\Request\ServerRequest $request) {
            return $request->hasParam("_lp");
        }, "context" => "Traffic\\Context\\LandingOfferContext"], ["test" => function (\Traffic\Request\ServerRequest $request) {
            if ($request->getParam("return") === "jsonp") {
                if (preg_match("/\\/([a-z0-9\\-_]+)\\/?\$/i", $request->getUri()->getPath(), $result)) {
                    return $result[1];
                }
                $params = $request->getQueryParams();
                return array_keys($params)[0];
            }
            return NULL;
        }, "param" => PARAM_CAMPAIGN, "context" => "Traffic\\Context\\KtrkContext"], ["test" => function (\Traffic\Request\ServerRequest $request) {
            if ($request->getParam("return") === "js.client") {
                if (preg_match("/\\/([a-z0-9\\-_]+)\\/?\$/i", $request->getUri()->getPath(), $result)) {
                    return $result[1];
                }
                $params = $request->getQueryParams();
                return array_keys($params)[0];
            }
            return NULL;
        }, "param" => PARAM_CAMPAIGN, "context" => "Traffic\\Context\\KClientJSContext"], ["pattern" => "/\\/([a-z0-9\\-_]+)\\/?\$/i", "context" => "Traffic\\Context\\ClickContext", "param" => PARAM_CAMPAIGN], ["pattern" => "/^\\/favicon\\.ico/", "context" => "Traffic\\Context\\NotFoundContext"], ["pattern" => "/^\\/robots\\.txt/", "context" => "Traffic\\Context\\RobotsContext"], ["pattern" => "/^\\/gateway\\.php/", "context" => "Traffic\\Context\\GatewayRedirectContext"], ["context" => "Traffic\\Context\\ClickContext"]];
    }
    public function match(\Traffic\Request\ServerRequest $serverRequest)
    {
        foreach ($this->_routes as $opts) {
            if ($param = $this->_test($opts, $serverRequest)) {
                $contextName = $opts["context"];
                $context = new $contextName();
                $serverRequest = $this->_updateParams($serverRequest, $opts, $param);
                return new TrafficRouterResult($serverRequest, $context);
            }
        }
        return new TrafficRouterResult($serverRequest, new \Traffic\Context\NotFoundContext());
    }
    private function _updateParams(\Traffic\Request\ServerRequest $request, $opts, $paramValue = NULL)
    {
        if (isset($opts["param"])) {
            $queryParams = $request->getQueryParams();
            $queryParams[$opts["param"]] = $paramValue;
            $request = $request->withQueryParams($queryParams);
        }
        return $request;
    }
    private function _test($opts, \Traffic\Request\ServerRequest $request)
    {
        if (!empty($opts["pattern"])) {
            if (preg_match($opts["pattern"], $request->getUri()->getPath(), $result)) {
                return isset($result[1]) ? $result[1] : true;
            }
            return false;
        }
        if (!empty($opts["test"])) {
            return call_user_func($opts["test"], $request);
        }
        return true;
    }
}

?>