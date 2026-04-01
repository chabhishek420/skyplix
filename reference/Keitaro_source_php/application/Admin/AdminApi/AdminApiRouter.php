<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\AdminApi;

class AdminApiRouter
{
    private $_version = NULL;
    private $_altoRouter = NULL;
    const PREFIX = "admin_api";
    public function __construct($version)
    {
        $this->_version = $version;
        AdminApiRoutesRepository::instance()->load();
        $this->_altoRouter = new \AltoRouter();
        $this->_altoRouter->setBasePath("/" . PREFIX . "/" . $this->_version);
        foreach (AdminApiRoutesRepository::instance()->getRoutes() as $route) {
            $this->_altoRouter->map($route["method"], $route["route"], $route["onMatch"]);
        }
    }
    public function match(\Traffic\Request\ServerRequest $request)
    {
        if (($resultRequest = $this->_matchWithRequest($request)) && !$resultRequest instanceof \Admin\Context\NoRouteRequest) {
            return $resultRequest;
        }
        $originalPath = $request->getUri()->getPath();
        $pathWithoutPrefix = preg_replace("/^\\/[^\\/]+\\//", "/", $originalPath);
        $newUri = $request->getUri()->withPath($pathWithoutPrefix);
        $requestWithoutPrefix = $request->withUri($newUri);
        return $this->_matchWithRequest($requestWithoutPrefix);
    }
    private function _matchWithRequest(\Traffic\Request\ServerRequest $request)
    {
        $match = $this->_altoRouter->match($request->getUri()->getPath(), $request->getMethod());
        if (empty($match)) {
            return $this->_buildNoRouteRequest($request);
        }
        if ($match["target"]) {
            $result = call_user_func_array($match["target"], $match["params"]);
            $additionalQueryParams = empty($result["params"]) ? [] : $result["params"];
            $additionalQueryParams["object"] = $result["controller"] . "." . $result["action"];
            $queryParams = $request->getQueryParams();
            $queryParams = array_merge($queryParams, $additionalQueryParams);
            $request = $request->withQueryParams($queryParams);
            return $request;
        }
        return $this->_buildNoRouteRequest($request);
    }
    public function _buildNoRouteRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        return new \Admin\Context\NoRouteRequest($serverRequest->getMethod(), $serverRequest->getUri(), $serverRequest->getHeaders());
    }
}

?>