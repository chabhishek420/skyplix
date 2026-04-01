<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Service;

class DomainService extends \Core\Entity\Service\EntityService
{
    const TRACKER_CODE_PREFIX = "domains";
    const CACHE_KEY_BASEPATH = "BASEPATH";
    public function definition()
    {
        return \Traffic\Model\Domain::definition();
    }
    public function findBasePath(\Traffic\Request\ServerRequest $serverRequest)
    {
        $isFromAdminAPI = false;
        $uri = $this->_filterAdminAPIBasePath($serverRequest->getUri(), $isFromAdminAPI);
        return \Traffic\Service\UrlService::instance()->getBasePathWithSlash($uri, $isFromAdminAPI ? 1 : 2);
    }
    public function cacheBasePath(\Traffic\Request\ServerRequest $serverRequest)
    {
        \Traffic\Cache\CacheService::instance()->commonCache()->set(CACHE_KEY_BASEPATH, $this->findBasePath($serverRequest));
    }
    public function findCachedBasePath()
    {
        try {
            return \Traffic\Cache\CacheService::instance()->commonCache()->get(CACHE_KEY_BASEPATH);
        } catch (\Traffic\Cache\NoCache $e) {
        }
    }
    public function urlWithBasePath(\Traffic\Model\Domain $domain, $basePath, $withHttps = false)
    {
        $scheme = $withHttps || $domain->isSSL() ? "https://" : "http://";
        $host = $domain->getName();
        $url = $scheme . $host . "/" . $basePath;
        return \Traffic\Service\UrlService::instance()->filterDoubleSlashes($url);
    }
    public function getTrackerCode()
    {
        return substr(md5(SALT . TRACKER_CODE_PREFIX), 3, 10);
    }
    public function nullifyByCampaign(\Traffic\Model\Campaign $campaign)
    {
        $where = [];
        $where[] = "default_campaign_id = " . $campaign->getId();
        $where[] = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        $this->updateMany(implode(" AND ", $where), ["default_campaign_id" => NULL]);
    }
    public function createMultiple($data)
    {
        if (!isset($data["name"])) {
            throw new \Core\Validator\ValidationError(["name" => "Is empty"]);
        }
        $names = explode(",", $data["name"]);
        $names = array_map(function ($name) {
            return $this->convertDomainName($name);
        }, $names);
        $this->_pruneDomainsInArchive($names);
        $dummy = $data;
        $result = [];
        foreach ($names as $name) {
            $newData = $dummy;
            $newData["name"] = $name;
            $result[] = self::create($newData);
        }
        return $result;
    }
    public function create($data)
    {
        $domains = $this->createMultiple($data);
        return array_shift($domains);
    }
    public function update(\Core\Entity\Model\EntityModelInterface $entity, $data)
    {
        if (!empty($data["name"])) {
            $data["name"] = $this->convertDomainName($data["name"]);
            if ($data["name"] !== $entity->getName()) {
                $data["ssl_status"] = \Traffic\Model\Domain::SSL_STATUS_AWAITING_DNS;
            }
        }
        return self::update($entity, $data);
    }
    public function disableOverspendingDomains()
    {
        $ids = \Component\Domains\Repository\DomainsRepository::instance()->getAllActiveIds();
        unset($ids[0]);
        $domains = \Component\Domains\Repository\DomainsRepository::instance()->allByIds($ids);
        foreach ($domains as $domain) {
            $this->archive($domain);
        }
    }
    public function convertDomainName($name)
    {
        $puny = new \TrueBV\Punycode();
        return $puny->encode(trim($name));
    }
    private function _filterAdminAPIBasePath(\Psr\Http\Message\UriInterface $uri, $isFromAdminAPI = false)
    {
        $uriPath = $uri->getPath();
        $adminApiPrefixPosition = strpos($uriPath, \Admin\AdminApi\AdminApiRouter::PREFIX);
        if ($adminApiPrefixPosition !== false) {
            $isFromAdminAPI = true;
            return $uri->withPath(substr($uriPath, 0, $adminApiPrefixPosition));
        }
        return $uri;
    }
    private function _pruneDomainsInArchive($_pruneDomainsInArchive, $domainNames)
    {
        $domainsTable = \Core\Db\Db::getPrefix() . "domains";
        $domainsIn = implode("', '", $domainNames);
        $sql = "DELETE FROM " . $domainsTable . " WHERE `name` IN ('" . $domainsIn . "') AND `state` = 'deleted';";
        \Core\Db\Db::instance()->execute($sql);
    }
}

?>