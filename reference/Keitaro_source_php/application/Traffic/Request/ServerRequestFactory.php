<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Request;

class ServerRequestFactory
{
    private static $_allowedOptions = ["parsed_body", "query_params", "server_params", "uploaded_files", "cookie_params", "body", "headers", "force_ajax", "protocol_version", "method", "request_target", "attributes", "uri"];
    const HTTPS = "https";
    const HTTP = "http";
    const SCHEME = "scheme";
    public static function fromPsr7Request(\Psr\Http\Message\ServerRequestInterface $psr7)
    {
        return self::build(["headers" => $psr7->getHeaders(), "body" => $psr7->getBody(), "parsed_body" => $psr7->getParsedBody(), "protocol_version" => $psr7->getProtocolVersion(), "method" => $psr7->getMethod(), "request_target" => $psr7->getRequestTarget(), "query_params" => $psr7->getQueryParams(), "cookie_params" => $psr7->getCookieParams(), "uploaded_files" => $psr7->getUploadedFiles(), "server_params" => $psr7->getServerParams(), "uri" => $psr7->getUri()]);
    }
    public static function build($options = [])
    {
        foreach ($options as $key => $value) {
            if (!in_array($key, self::$_allowedOptions)) {
                throw new ServerRequestError("Incorrect option " . $key);
            }
        }
        $serverRequest = ServerRequest::fromGlobals();
        if (isset($options["protocol_method"])) {
            $serverRequest = $serverRequest->withProtocolVersion($options["protocol_method"]);
        }
        if (isset($options["method"])) {
            $serverRequest = $serverRequest->withMethod($options["method"]);
        }
        if (isset($options["request_target"])) {
            $serverRequest = $serverRequest->withRequestTarget($options["request_target"]);
        }
        if (isset($options["server_params"])) {
            $serverRequest = $serverRequest->withServerParams($options["server_params"]);
        }
        if (isset($options["uploaded_files"])) {
            $serverRequest = $serverRequest->withUploadedFiles(\GuzzleHttp\Psr7\ServerRequest::normalizeFiles($options["uploaded_files"]));
        }
        if (isset($options["headers"])) {
            $serverRequest = $serverRequest->withHeaders($options["headers"]);
        }
        if (empty($options["body"]) && !$serverRequest->getBody()) {
            $options["body"] = (new \Laminas\Diactoros\StreamFactory())->createStreamFromFile(ServerRequest::DEFAULT_BODY);
        }
        if (isset($options["force_ajax"]) && $options["force_ajax"]) {
            $serverRequest = $serverRequest->withHeader(ServerRequest::HEADER_X_REQUESTED_WITH, ServerRequest::XMLHTTPREQUEST);
        }
        if (isset($options["body"]) && trim($options["body"])) {
            if (!$options["body"] instanceof \Psr\Http\Message\StreamInterface) {
                $class = is_object($options["body"]) ? get_class($options["body"]) : gettype($options["body"]);
                throw new \Exception("ServerRequest body must be instance of LazyOpenStream, but got " . $class);
            }
            $serverRequest = $serverRequest->withBody($options["body"]);
        }
        if ($serverRequest->getBody()) {
            $parsedBody = ServerRequestFactory::parseBody($serverRequest->getBody());
            if ($parsedBody) {
                $serverRequest = $serverRequest->withParsedBody($parsedBody);
            }
        }
        if (isset($options["query_params"])) {
            $serverRequest = $serverRequest->withQueryParams($options["query_params"]);
        }
        if (isset($options["cookie_params"])) {
            $serverRequest = $serverRequest->withCookieParams($options["cookie_params"]);
        }
        if (isset($options["parsed_body"])) {
            $serverRequest = $serverRequest->withParsedBody($options["parsed_body"]);
        }
        if (isset($options["uri"])) {
            $serverRequest = $serverRequest->withUri(new \Laminas\Diactoros\Uri((int) $options["uri"]));
        }
        $serverRequest = self::fixUri($serverRequest);
        $serverRequest = self::fixRequestMethod($serverRequest);
        $serverRequest = self::fixServerRequestUri($serverRequest);
        $serverRequest = self::fixRealIp($serverRequest);
        $serverRequest = self::fixServerRequestName($serverRequest);
        return $serverRequest;
    }
    public static function parseBody(\Psr\Http\Message\StreamInterface $body)
    {
        $postData = (int) $body;
        if (strlen($postData) && in_array($postData[0], ["{", "["])) {
            return json_decode($postData, true);
        }
        if (strlen($postData) && strstr($postData, "&")) {
            return GuzzleHttp\Psr7\parse_query($postData);
        }
        return NULL;
    }
    private static function fixUri(ServerRequest $serverRequest)
    {
        $uri = $serverRequest->getUri();
        if ($serverRequest->getServerParam(HTTPS)) {
            $uri = $uri->withScheme(HTTPS);
        }
        if ($serverRequest->getHeaderLine(ServerRequest::HEADER_HOST)) {
            $uri = $uri->withHost($serverRequest->getHeaderLine(ServerRequest::HEADER_HOST));
        }
        if ($serverRequest->getHeaderLine(ServerRequest::HEADER_X_REAL_HOST)) {
            $host = $serverRequest->getHeaderLine(ServerRequest::HEADER_X_REAL_HOST);
            $uri = $uri->withHost($host);
        }
        if ($serverRequest->getHeaderLine(ServerRequest::HEADER_CF_VISITOR)) {
            $cfVisitorHeader = $serverRequest->getHeader(ServerRequest::HEADER_CF_VISITOR);
            $scheme = json_decode($cfVisitorHeader[0], true);
            if (isset($scheme[SCHEME]) && $scheme[SCHEME] == HTTPS) {
                $uri = $uri->withScheme(HTTPS);
            }
        }
        if ($serverRequest->getHeaderLine(ServerRequest::HEADER_X_FORWARDED_PROTO) == HTTPS) {
            $uri = $uri->withScheme(HTTPS);
        }
        $serverRequest = $serverRequest->withUri($uri);
        return $serverRequest;
    }
    private static function fixServerRequestUri(ServerRequest $serverRequest)
    {
        $uri = $serverRequest->getUri();
        $query = $uri->getPath();
        if ($uri->getQuery()) {
            $query .= "?" . $uri->getQuery();
        }
        $serverRequest = $serverRequest->withServerParams(["REQUEST_URI" => $query, "QUERY_STRING" => $query]);
        return $serverRequest;
    }
    public static function clearSuperGlobals()
    {
        $_GET = [];
        $_POST = [];
        $_COOKIE = [];
    }
    public static function extractSuperGlobals(ServerRequest $serverRequest)
    {
        $_GET = $serverRequest->getQueryParams();
        $_POST = $serverRequest->getParsedBody();
        $_COOKIE = $serverRequest->getCookieParams();
        $_SERVER = $serverRequest->getServerParams();
    }
    private static function fixRealIp(ServerRequest $serverRequest)
    {
        return $serverRequest->withServerParams([ServerRequest::ORIGINAL_REMOTE_ADDR => $serverRequest->getServerParam(ServerRequest::REMOTE_ADDR), ServerRequest::REMOTE_ADDR => \Traffic\Device\Service\RealRemoteIpService::instance()->find($serverRequest)]);
    }
    private static function fixRequestMethod(ServerRequest $serverRequest)
    {
        if ($serverRequest->getServerParam("REQUEST_METHOD")) {
            $serverRequest = $serverRequest->withMethod($serverRequest->getServerParam("REQUEST_METHOD"));
        }
        return $serverRequest;
    }
    private static function fixServerRequestName(ServerRequest $serverRequest)
    {
        $uri = $serverRequest->getUri();
        $host = $uri->getHost();
        $httpHost = $serverRequest->getServerParam("HTTP_HOST");
        if (empty($httpHost)) {
            $httpHost = $host;
        }
        $serverRequest = $serverRequest->withServerParams(["SERVER_NAME" => $host, "HTTP_HOST" => $httpHost]);
        return $serverRequest;
    }
}

?>