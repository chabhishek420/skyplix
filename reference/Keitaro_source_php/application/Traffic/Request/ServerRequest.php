<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Request;

class ServerRequest extends \GuzzleHttp\Psr7\ServerRequest
{
    const POST = "POST";
    const GET = "GET";
    const DEFAULT_BODY = "php://input";
    const XMLHTTPREQUEST = "xmlhttprequest";
    const HEADER_X_REQUESTED_WITH = "X-Requested-With";
    const HEADER_ACCEPT = "ACCEPT";
    const HEADER_ACCEPT_LANGUAGE = "Accept-Language";
    const HEADER_CF_IPCOUNTRY = "CF-IPCOUNTRY";
    const HEADER_CF_CONNECTING_IP = "CF-CONNECTING-IP";
    const HEADER_USER_AGENT = "User-Agent";
    const HEADER_REFERER = "Referer";
    const HEADER_X_REAL_HOST = "X-REAL-HOST";
    const HEADER_X_REAL_IP = "X-REAL-IP";
    const HEADER_HOST = "HOST";
    const HEADER_X_FORWARDED_PROTO = "X-FORWARDED-PROTO";
    const HEADER_CF_VISITOR = "CF-VISITOR";
    const HEADER_X_FORWARDED_FOR = "X-FORWARDED-FOR";
    const HEADER_FORWARDED = "Forwarded";
    const REMOTE_ADDR = "REMOTE_ADDR";
    const ORIGINAL_REMOTE_ADDR = "ORIGINAL_REMOTE_ADDR";
    public static function fromGlobals()
    {
        $method = isset($_SERVER["REQUEST_METHOD"]) ? $_SERVER["REQUEST_METHOD"] : "GET";
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $body = new \GuzzleHttp\Psr7\LazyOpenStream("php://input", "r+");
        $protocol = isset($_SERVER["SERVER_PROTOCOL"]) ? str_replace("HTTP/", "", $_SERVER["SERVER_PROTOCOL"]) : "1.1";
        $serverRequest = new ServerRequest($method, $uri, $headers, $body, $protocol, $_SERVER);
        return $serverRequest->withCookieParams($_COOKIE)->withQueryParams($_GET)->withParsedBody($_POST)->withUploadedFiles(self::normalizeFiles($_FILES));
    }
    public static function build($options = [])
    {
        return ServerRequestFactory::build($options);
    }
    public function hasServerParam($name)
    {
        return array_key_exists($name, $this->getServerParams());
    }
    public function getServerParam($name)
    {
        $serverParams = $this->getServerParams();
        return isset($serverParams[$name]) ? $serverParams[$name] : NULL;
    }
    public function hasParam($param)
    {
        if (is_array($this->getQueryParams()) && array_key_exists($param, $this->getQueryParams())) {
            return true;
        }
        if (is_array($this->getParsedBody()) && array_key_exists($param, $this->getParsedBody())) {
            return true;
        }
        return false;
    }
    public function getQueryParam($name)
    {
        $queryParams = $this->getQueryParams();
        return isset($queryParams[$name]) ? $queryParams[$name] : NULL;
    }
    public function getParsedBodyParam($name)
    {
        $parsedBody = $this->getParsedBody();
        return isset($parsedBody[$name]) ? $parsedBody[$name] : NULL;
    }
    public function getAllParams()
    {
        return array_merge($this->getQueryParams(), is_array($this->getParsedBody()) ? $this->getParsedBody() : []);
    }
    public function getParam($name)
    {
        if (array_key_exists($name, $this->getQueryParams())) {
            return $this->getQueryParam($name);
        }
        if (is_array($this->getParsedBody()) && array_key_exists($name, $this->getParsedBody())) {
            return $this->getParsedBodyParam($name);
        }
        return NULL;
    }
    public function getCookieParam($name)
    {
        $params = $this->getCookieParams();
        return isset($params[$name]) ? $params[$name] : NULL;
    }
    public function isPost()
    {
        return $this->getParsedBody() || $this->getMethod() == POST;
    }
    public function isAjax()
    {
        if ($this->getHeaderLine(HEADER_ACCEPT) && strstr($this->getHeaderLine(HEADER_ACCEPT), "application/json")) {
            return true;
        }
        return $this->getHeaderLine(HEADER_X_REQUESTED_WITH) && strtolower($this->getHeaderLine(HEADER_X_REQUESTED_WITH)) === XMLHTTPREQUEST;
    }
    public function withHeaders($headers)
    {
        $new = clone $this;
        foreach ($headers as $name => $value) {
            try {
                $new = $new->withHeader($name, $value);
            } catch (\InvalidArgumentException $e) {
            }
        }
        return $new;
    }
    public function withCookieParam($name, $value)
    {
        $cookieParams = $this->getCookieParams();
        $cookieParams[$name] = $value;
        return $this->withCookieParams($cookieParams);
    }
    public function withServerParams($serverParams)
    {
        $serverParams = array_merge($this->getServerParams(), $serverParams);
        $newServerRequest = new ServerRequest($this->getMethod(), $this->getUri(), $this->getHeaders(), $this->getBody(), $this->getProtocolVersion(), $serverParams);
        $newServerRequest = $newServerRequest->withCookieParams($this->getCookieParams())->withQueryParams($this->getQueryParams())->withParsedBody($this->getParsedBody())->withUploadedFiles($this->getUploadedFiles());
        return $newServerRequest;
    }
    public function withHeader($header, $value)
    {
        return self::withHeader($header, $value);
    }
}

?>