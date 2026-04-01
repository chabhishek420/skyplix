<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Cookies\Service;

class CookiesService extends \Traffic\Service\AbstractService
{
    const SUB_ID_KEY = "_subid";
    public function encodeAndSet(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response, $cookieName, $data, $expire = NULL)
    {
        $json = $this->_encode($data);
        return $this->setRaw($serverRequest, $response, $cookieName, $json, $expire);
    }
    public function setRaw(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response, $cookieName, $rawData, $expire = NULL, $httpOnly = false, $path = "/", $domain = NULL)
    {
        $serverRequest = $this->_updateRequestCookie($serverRequest, $cookieName, $rawData);
        if (empty($expire)) {
            $expire = $this->_getDefaultMaxAge();
        }
        $cookies = new \BjoernGoetschke\Psr7Cookies\HttpResponseCookies($response);
        $cookies->setCookieFor($cookieName, $rawData, $expire, $path, $domain, false, $httpOnly);
        $response = $cookies->getResponse();
        return [$serverRequest, $response];
    }
    public function setRaws(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response, $cookiesArray, $expire = NULL, $httpOnly = false, $path = "/", $domain = NULL)
    {
        if (empty($expire)) {
            $expire = $this->_getDefaultMaxAge();
        }
        $cookies = new \BjoernGoetschke\Psr7Cookies\HttpResponseCookies($response);
        foreach ($cookiesArray as $key => $value) {
            $serverRequest = $this->_updateRequestCookie($serverRequest, $key, $value);
            $cookies->setCookieFor($key, $value, $expire, $path, $domain, false, $httpOnly);
        }
        $response = $cookies->getResponse();
        return [$serverRequest, $response];
    }
    public function unsetCookie(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response, $cookieName)
    {
        $cookies = new \BjoernGoetschke\Psr7Cookies\HttpResponseCookies($response);
        $cookies->unsetCookie($cookieName);
        $response = $cookies->getResponse();
        return [$serverRequest, $response];
    }
    public function decodeAndGet(\Traffic\Request\ServerRequest $serverRequest, $cookieName)
    {
        if ($serverRequest->getCookieParam($cookieName)) {
            return $this->_decode($serverRequest->getCookieParam($cookieName));
        }
        return NULL;
    }
    public function getAll(\Traffic\Request\ServerRequest $serverRequest)
    {
        return $serverRequest->getCookieParams();
    }
    public function getRaw(\Traffic\Request\ServerRequest $serverRequest, $cookieName)
    {
        return $serverRequest->getCookieParam($cookieName);
    }
    private function _encode($data)
    {
        try {
            return \Firebase\JWT\JWT::encode(["data" => json_encode($data)], SALT, "HS256");
        } catch (\Exception $e) {
            \Traffic\Logging\Service\LoggerService::instance()->debug("Sentinel error: " . $e->getMessage());
        }
    }
    private function _decode($rawData)
    {
        try {
            $token = \Firebase\JWT\JWT::decode($rawData, SALT, ["HS256"]);
            if (!empty($token->data)) {
                return json_decode($token->data, true);
            }
        } catch (\Exception $e) {
            \Traffic\Logging\Service\LoggerService::instance()->debug("JWT error: " . $e->getMessage() . " (" . (int) $rawData . ")");
        }
    }
    private function _getDefaultMaxAge()
    {
        return 2678400;
    }
    private function _updateRequestCookie(\Traffic\Request\ServerRequest $serverRequest, $cookieName, $rawData)
    {
        $cookieParams = $serverRequest->getCookieParams();
        $cookieParams[$cookieName] = $rawData;
        return $serverRequest->withCookieParams($cookieParams);
    }
}

?>