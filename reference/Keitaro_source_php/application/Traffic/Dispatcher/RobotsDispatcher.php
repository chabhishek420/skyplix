<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class RobotsDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    const ROBOTS_DISALLOW = "User-agent: *\nDisallow: /";
    const ROBOTS_ALLOW = "User-agent: *\nAllow: /";
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = \Traffic\Response\Response::build(["status" => 200, "headers" => [\Traffic\Response\ContentType::HEADER => \Traffic\Response\ContentType::TEXT], "body" => $this->_robotsContent($request), "disable_cache" => true]);
        return $response;
    }
    private function _robotsContent(\Traffic\Request\ServerRequest $request)
    {
        $robots = $this->_findDomainRobots($request->getUri());
        if (!$robots) {
            return ROBOTS_DISALLOW;
        }
        return ROBOTS_ALLOW;
    }
    private function _findDomainRobots(\Psr\Http\Message\UriInterface $uri)
    {
        $domains = \Traffic\Repository\CachedDomainRepository::instance()->allActiveCached();
        $requestedDomain = $uri->getHost();
        foreach ($domains as $domain) {
            if ($domain->getName() === $requestedDomain) {
                return $domain->getAllowIndexing();
            }
        }
        return true;
    }
}

?>