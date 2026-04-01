<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Session\Service;

class UniquenessSessionService extends \Traffic\Service\AbstractService
{
    public function saveCookies(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response, \Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign, \Traffic\Model\BaseStream $stream = NULL)
    {
        if ($rawClick->alwaysEmptyCookies()) {
            return [$serverRequest, $response];
        }
        $storage = $this->_getCookieStorage($serverRequest, $response);
        $this->_saveSessionEntry($storage, $campaign, $rawClick, $stream);
        $newRequest = $storage->getServerRequest();
        $newResponse = $storage->getResponse();
        return [$newRequest, $newResponse];
    }
    public function saveNonCookies(\Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign, \Traffic\Model\BaseStream $stream = NULL, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $storage = $this->_getNonCookieStorage();
        if (empty($storage)) {
            $logEntry->add("Non cookie uniqueness storage is empty. No redis or mysql available");
        } else {
            $this->_saveSessionEntry($storage, $campaign, $rawClick, $stream);
        }
    }
    public function isUniqueForCampaign($isUniqueForCampaign, \Traffic\Request\ServerRequest $serverRequest, \Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign)
    {
        $entries = $this->_getSessions($serverRequest, $rawClick, $campaign);
        foreach ($entries as $entry) {
            if (!$entry->isUniqueForCampaign($rawClick, $campaign)) {
                return false;
            }
        }
        return true;
    }
    public function isUniqueForStream($isUniqueForStream, \Traffic\Request\ServerRequest $serverRequest, \Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign, \Traffic\Model\BaseStream $stream)
    {
        $entries = $this->_getSessions($serverRequest, $rawClick, $campaign);
        foreach ($entries as $entry) {
            if (!$entry->isUniqueForStream($rawClick, $campaign, $stream)) {
                return false;
            }
        }
        return true;
    }
    public function isUniqueGlobal($isUniqueGlobal, \Traffic\Request\ServerRequest $serverRequest, \Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign)
    {
        $entries = $this->_getSessions($serverRequest, $rawClick, $campaign);
        foreach ($entries as $entry) {
            if (!$entry->isUniqueGlobal($rawClick, $campaign)) {
                return false;
            }
        }
        return true;
    }
    public function getUniquenessId($getUniquenessId, \Traffic\Model\Campaign $campaign, \Traffic\RawClick $rawClick)
    {
        $uniquenessId = $rawClick->getIpString();
        if ($campaign->isUniqueByIpUa()) {
            $uniquenessId .= $rawClick->getUserAgent();
        }
        return md5($uniquenessId);
    }
    public function getDeprecatedUniquenessId($getDeprecatedUniquenessId, \Traffic\Model\Campaign $campaign, \Traffic\RawClick $rawClick)
    {
        $uniquenessId = $rawClick->getIpString();
        if ($campaign->isUniqueByIpUa()) {
            $uniquenessId .= $rawClick->getUserAgent();
        }
        return murmurhash3($uniquenessId);
    }
    private function _saveSessionEntry(\Traffic\Session\Storage\StorageInterface $storage, \Traffic\Model\Campaign $campaign, \Traffic\RawClick $rawClick, \Traffic\Model\BaseStream $stream = NULL)
    {
        $uniquenessId = $this->getUniquenessId($campaign, $rawClick);
        $entry = $storage->getSessionEntry($uniquenessId);
        if (empty($entry)) {
            $entry = \Traffic\Session\SessionEntry::restore([]);
        }
        $entry->touch($rawClick, $campaign, $stream);
        $storage->save($uniquenessId, $entry, $this->_getTTL($campaign));
    }
    private function _getTTL(\Traffic\Model\Campaign $campaign)
    {
        $ttl = $campaign->getCookiesTtl();
        return 3600 * $ttl;
    }
    private function _getCookieStorage(\Traffic\Session\Storage\CookiesStorage $_getCookieStorage, \Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response)
    {
        $cookieStorage = new \Traffic\Session\Storage\CookiesStorage($serverRequest, $response);
        return $cookieStorage;
    }
    private function _getNonCookieStorage(\Traffic\Session\Storage\StorageInterface $_getNonCookieStorage)
    {
        if (\Traffic\Redis\Service\RedisStorageService::instance()->draftStorageUsesRedis()) {
            return new \Traffic\Session\Storage\RedisStorage();
        }
        if (\Core\Db\Db::instance()->isEnabled()) {
            return new \Traffic\Session\Storage\MysqlStorage();
        }
        return NULL;
    }
    private function _getSessions(\Traffic\Request\ServerRequest $serverRequest, \Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign)
    {
        $response = new \Traffic\Response\Response();
        $entries = [];
        $uniquenessId = $this->getUniquenessId($campaign, $rawClick);
        $deprecatedUniquenessId = $this->getDeprecatedUniquenessId($campaign, $rawClick);
        $cookieStorage = $this->_getCookieStorage($serverRequest, $response);
        $nonCookieStorage = $this->_getNonCookieStorage();
        if (!empty($nonCookieStorage)) {
            $entry = $nonCookieStorage->getSessionEntry($uniquenessId);
            if (!empty($entry)) {
                $entries[] = $entry;
            } else {
                $entry = $nonCookieStorage->getSessionEntry($deprecatedUniquenessId);
                if (!empty($entry)) {
                    $entries[] = $entry;
                }
            }
        }
        if (!$rawClick->alwaysEmptyCookies() && $campaign->isUniquenessUseCookies()) {
            $entry = $cookieStorage->getSessionEntry($uniquenessId);
            if (!empty($entry)) {
                $entries[] = $entry;
            } else {
                $entry = $cookieStorage->getSessionEntry($deprecatedUniquenessId);
                if (!empty($entry)) {
                    $entries[] = $entry;
                }
            }
        }
        return $entries;
    }
}

?>