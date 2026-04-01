<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class DomainRedirectStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $request = $payload->getServerRequest();
        if (filter_var($request->getUri()->getHost(), FILTER_VALIDATE_IP)) {
            return $payload;
        }
        $redirect = $this->_findDomainRedirect($request->getUri());
        if ($redirect && $redirect !== $request->getUri()->getScheme() && $this->_checkCloudFlareScheme($request)) {
            $path = str_replace($request->getUri()->getScheme() . "://", $redirect . "://", (int) $request->getUri());
            $response = $payload->getResponse()->withStatus(\Traffic\Response\StatusCode::MOVED_PERMANENTLY)->withHeader("Location", $path);
            $payload->setResponse($response);
            $payload->abort();
        }
        return $payload;
    }
    private function _checkCloudFlareScheme(\Traffic\Request\ServerRequest $serverRequest)
    {
        if ($serverRequest->getHeaderLine(\Traffic\Request\ServerRequest::HEADER_CF_VISITOR)) {
            $cfVisitorHeader = $serverRequest->getHeader(\Traffic\Request\ServerRequest::HEADER_CF_VISITOR);
            $scheme = json_decode($cfVisitorHeader[0], true);
            if (isset($scheme[\Traffic\Request\ServerRequestFactory::SCHEME]) && $scheme[\Traffic\Request\ServerRequestFactory::SCHEME] == \Traffic\Request\ServerRequestFactory::HTTPS) {
                return false;
            }
        }
        return true;
    }
    private function _findDomainRedirect(\Psr\Http\Message\UriInterface $uri)
    {
        $domains = \Traffic\Repository\CachedDomainRepository::instance()->allActiveCached();
        $requestedDomain = $uri->getHost();
        foreach ($domains as $domain) {
            if ($domain->getName() === $requestedDomain) {
                return $domain->getSSLRedirect();
            }
        }
        return false;
    }
}

?>