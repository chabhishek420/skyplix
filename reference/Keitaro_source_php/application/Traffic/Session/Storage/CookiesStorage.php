<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Session\Storage;

class CookiesStorage implements StorageInterface
{
    private $_serverRequest = NULL;
    private $_response = NULL;
    public function __construct(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response)
    {
        $this->_serverRequest = $serverRequest;
        $this->_response = $response;
    }
    public function save($save, $uniquenessId, \Traffic\Session\SessionEntry $entry, string $ttlInSec)
    {
        $entry = $entry->cleanOldSessionForCookies();
        $data = $entry->getData();
        list($this->_serverRequest, $this->_response) = \Traffic\Cookies\Service\CookiesService::instance()->encodeAndSet($this->_serverRequest, $this->_response, self::getCookieName(), $data, time() + $ttlInSec);
    }
    public function getSessionEntry(\Traffic\Session\SessionEntry $getSessionEntry, $uniquenessId)
    {
        $data = \Traffic\Cookies\Service\CookiesService::instance()->decodeAndGet($this->_serverRequest, self::getCookieName());
        return \Traffic\Session\SessionEntry::restore($data);
    }
    public function getServerRequest(\Traffic\Request\ServerRequest $getServerRequest)
    {
        return $this->_serverRequest;
    }
    public function getResponse(\Traffic\Response\Response $getResponse)
    {
        return $this->_response;
    }
    public static function getCookieName()
    {
        return substr(md5(SALT), 2, 5);
    }
}

?>