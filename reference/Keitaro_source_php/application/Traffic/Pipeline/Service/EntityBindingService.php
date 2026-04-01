<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Service;

class EntityBindingService
{
    private $_campaign = NULL;
    private $_rawClick = NULL;
    private $_logEntry = NULL;
    const TYPE_STREAM_BINDING = "s";
    const TYPE_LANDING_BINDING = "lp";
    const TYPE_OFFER_BINDING = "of";
    const BIND_TYPE_REDIS = "redis";
    const BIND_TYPE_COOKIE = "cookie";
    const IP_SUFFIX = "ip";
    const _BINDING_TYPE_DIC = NULL;
    public function __construct(\Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $this->_rawClick = $rawClick;
        $this->_campaign = $campaign;
        $this->_logEntry = $logEntry;
    }
    public function findBoundEntity(string $findBoundEntity, \Traffic\Request\ServerRequest $serverRequest, $type)
    {
        $uniquenessID = \Traffic\Session\Service\UniquenessSessionService::instance()->getUniquenessId($this->_campaign, $this->_rawClick);
        $entityId = $this->_checkRedisBinding($uniquenessID, $type);
        if (!empty($entityId)) {
            return $entityId;
        }
        $deprecatedUniquenessID = \Traffic\Session\Service\UniquenessSessionService::instance()->getDeprecatedUniquenessId($this->_campaign, $this->_rawClick);
        $entityId = $this->_checkRedisBinding($deprecatedUniquenessID, $type);
        if (!empty($entityId)) {
            return $entityId;
        }
        $cookiesEnabled = \Traffic\Repository\CachedSettingsRepository::instance()->get("cookies_enabled", 1);
        if (!$cookiesEnabled) {
            return $entityId;
        }
        $cookieName = $this->_getCookieName($type);
        $entityId = \Traffic\Cookies\Service\CookiesService::instance()->decodeAndGet($serverRequest, $cookieName);
        if (!empty($entityId)) {
            $prevIPCookieName = $this->_getPrevIPCookieName($type);
            $prevIP = \Traffic\Cookies\Service\CookiesService::instance()->decodeAndGet($serverRequest, $prevIPCookieName);
            $this->_logMessage($type, $entityId, BIND_TYPE_COOKIE, $prevIP);
        }
        return $entityId;
    }
    public function bindEntityRedis($bindEntityRedis, $type, $entityId)
    {
        $ttl = $this->_getTTLInSeconds($this->_campaign);
        $typeName = _BINDING_TYPE_DIC[$type];
        $this->_logEntry->add("Binding " . $typeName . " in redis for " . $ttl . " seconds");
        $uniquenessID = \Traffic\Session\Service\UniquenessSessionService::instance()->getUniquenessId($this->_campaign, $this->_rawClick);
        $redisKey = $this->_getRedisKey($type, $uniquenessID);
        \Traffic\Redis\Service\RedisStorageService::instance()->setEx($redisKey, $ttl, $entityId);
        $prevIPKey = $this->_getPrevIPRedisKey($type, $uniquenessID);
        \Traffic\Redis\Service\RedisStorageService::instance()->setEx($prevIPKey, $ttl, $this->_rawClick->getIpString());
    }
    public function bindEntityCookies($bindEntityCookies, \Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response, $type, $entityId)
    {
        $expire = $this->_getCookiesExpire($this->_campaign) * 60 * 60;
        $cookieName = $this->_getCookieName($type, $this->_campaign);
        list($serverRequest, $response) = \Traffic\Cookies\Service\CookiesService::instance()->encodeAndSet($serverRequest, $response, $cookieName, $entityId, $expire);
        $prevIPCookieName = $this->_getPrevIPCookieName($type, $this->_campaign);
        list($serverRequest, $response) = \Traffic\Cookies\Service\CookiesService::instance()->encodeAndSet($serverRequest, $response, $prevIPCookieName, $this->_rawClick->getIpString(), $expire);
        return [$serverRequest, $response];
    }
    private function _getPrefix()
    {
        return substr(md5(SALT), 5, 8);
    }
    private function _getCookieName($type)
    {
        return $this->_getPrefix() . $type . $this->_campaign->getId();
    }
    private function _getRedisKey($type, $uniquenessID)
    {
        return $this->_getPrefix() . ":" . $uniquenessID . ":" . $type . ":" . $this->_campaign->getId();
    }
    private function _getPrevIPCookieName($type)
    {
        return $this->_getCookieName($type, $this->_campaign) . IP_SUFFIX;
    }
    private function _getPrevIPRedisKey($type, $uniquenessID)
    {
        return $this->_getRedisKey($type, $uniquenessID, $this->_campaign) . ":" . IP_SUFFIX;
    }
    private function _getCookiesExpire(\Traffic\Model\Campaign $campaign)
    {
        $ttl = $campaign->getCookiesTtl();
        return time() + 3600 * $ttl;
    }
    private function _getTTLInSeconds(\Traffic\Model\Campaign $campaign)
    {
        $ttl = $campaign->getCookiesTtl();
        return 3600 * $ttl;
    }
    private function _logMessage($type, $entityId, $bindType, $prevIP)
    {
        $typeName = _BINDING_TYPE_DIC[$type];
        $msg = "Visitor is bound by " . $bindType . " to " . $typeName . " #" . $entityId;
        if (!empty($prevIP)) {
            $msg .= " (previous IP was " . $prevIP . ")";
        }
        $this->_logEntry->add($msg);
    }
    private function _checkRedisBinding(string $_checkRedisBinding, $uniquenessID, $type)
    {
        $redisKey = $this->_getRedisKey($type, $uniquenessID);
        $entityId = \Traffic\Redis\Service\RedisStorageService::instance()->get($redisKey);
        if (!empty($entityId)) {
            $prevIPKey = $this->_getPrevIPRedisKey($type, $uniquenessID);
            $prevIP = \Traffic\Redis\Service\RedisStorageService::instance()->get($prevIPKey);
            $this->_logMessage($type, $entityId, BIND_TYPE_REDIS, $prevIP);
            return $entityId;
        }
        return NULL;
    }
}

?>