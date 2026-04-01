<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Repository;

class CachedDomainRepository extends AbstractBaseRepository
{
    public function allActiveCached()
    {
        try {
            return \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\CachedData\DataGetter\GetDomains::NAME);
        } catch (\Traffic\Cache\NoCache $e) {
            return [];
        }
    }
    public function getCampaignIdByUrl(\Psr\Http\Message\UriInterface $uri)
    {
        $domains = $this->allActiveCached();
        $requestedDomain = $uri->getHost();
        $pathExists = $uri->getPath() !== "/" && $uri->getPath() !== "";
        foreach ($domains as $domain) {
            $campaign = NULL;
            if ($domain->getName() === $requestedDomain) {
                $campaign = $domain->getDefaultCampaignId();
            }
            if (!$campaign && $domain->isWildcard()) {
                $campaign = $this->_tryFindAsSubdomain($domain, $requestedDomain);
            }
            if ($campaign && (!$pathExists || $domain->get("catch_not_found"))) {
                return $campaign;
            }
        }
        return NULL;
    }
    private function _tryFindAsSubdomain(\Traffic\Model\Domain $domain, $requestedDomain)
    {
        $requestedDomainParts = isset($requestedDomain) ? explode(".", $requestedDomain) : NULL;
        if (!empty($requestedDomainParts)) {
            $domainHostParts = explode(".", $domain->getName());
            $similar = true;
            $i = count($domainHostParts) - 1;
            $j = count($requestedDomainParts) - 1;
            while (0 <= $i) {
                if ($domainHostParts[$i] != $requestedDomainParts[$j]) {
                    $similar = false;
                } else {
                    $i--;
                    $j--;
                }
            }
            if ($similar) {
                return $domain->getDefaultCampaignId();
            }
        }
    }
}

?>