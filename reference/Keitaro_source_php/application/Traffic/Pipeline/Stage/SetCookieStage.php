<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class SetCookieStage
{
    const HEADER_LIMIT_FOR_COOKIES = 3060;
    private function _areCookiesEnabled($payload)
    {
        return \Traffic\Repository\CachedSettingsRepository::instance()->get("cookies_enabled", 1);
    }
    public function process(\Traffic\Pipeline\Payload $process, \Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        if (!$this->_areCookiesEnabled($payload)) {
            return $payload;
        }
        $payload = $this->_setGenericCookies($payload, $logEntry);
        if ($payload->isCookieLandingBinded()) {
            $landing = $payload->getLanding();
            if (!empty($landing)) {
                $payload = $this->_setBindingCookies($payload, $logEntry, \Traffic\Pipeline\Service\EntityBindingService::TYPE_LANDING_BINDING, $landing->getId());
            }
        }
        if ($payload->isCookieOfferBinded()) {
            $offer = $payload->getOffer();
            if (!empty($offer)) {
                $payload = $this->_setBindingCookies($payload, $logEntry, \Traffic\Pipeline\Service\EntityBindingService::TYPE_OFFER_BINDING, $offer->getId());
            }
        }
        if ($payload->isCookieStreamBinded()) {
            $stream = $payload->getStream();
            if (!empty($stream)) {
                $payload = $this->_setBindingCookies($payload, $logEntry, \Traffic\Pipeline\Service\EntityBindingService::TYPE_STREAM_BINDING, $stream->getId());
            }
        }
        $payload = $this->_setTokenCookie($payload, $logEntry);
        $payload = $this->_setUniquenessId($payload, $logEntry);
        return $payload;
    }
    private function _setTokenCookie(\Traffic\Pipeline\Payload $_setTokenCookie, \Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        if (!$payload->isSaveTokenRequired()) {
            return $payload;
        }
        $rawClick = $payload->getRawClick();
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        $token = $rawClick->getToken();
        if (strlen($token) < HEADER_LIMIT_FOR_COOKIES) {
            list($serverRequest, $response) = \Traffic\Cookies\Service\CookiesService::instance()->setRaw($serverRequest, $response, GenerateTokenStage::TOKEN_PARAM, $token);
            $payload->setServerRequest($serverRequest);
            $payload->setResponse($response);
        }
        return $payload;
    }
    private function _setBindingCookies(\Traffic\Pipeline\Payload $_setBindingCookies, \Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry, $type, $entityID)
    {
        $rawClick = $payload->getRawClick();
        $campaign = $payload->getCampaign();
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        $entityBinding = new \Traffic\Pipeline\Service\EntityBindingService($rawClick, $campaign, $logEntry);
        list($serverRequest, $response) = $entityBinding->bindEntityCookies($serverRequest, $response, $type, $entityID);
        $payload->setServerRequest($serverRequest);
        $payload->setResponse($response);
        return $payload;
    }
    private function _setGenericCookies(\Traffic\Pipeline\Payload $_setGenericCookies, \Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $rawClick = $payload->getRawClick();
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        if (empty($response)) {
            throw new StageException("response is not set");
        }
        list($serverRequest, $response) = \Traffic\Cookies\Service\CookiesService::instance()->setRaw($serverRequest, $response, \Traffic\Cookies\Service\CookiesService::SUB_ID_KEY, $rawClick->getSubId());
        $payload->setServerRequest($serverRequest);
        $payload->setResponse($response);
        return $payload;
    }
    private function _setUniquenessId(\Traffic\Pipeline\Payload $_setUniquenessId, \Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        if (!$payload->isSaveUniquenessRequired()) {
            return $payload;
        }
        $rawClick = $payload->getRawClick();
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        $campaign = $payload->getCampaign();
        $stream = $payload->getStream();
        list($serverRequest, $response) = \Traffic\Session\Service\UniquenessSessionService::instance()->saveCookies($serverRequest, $response, $rawClick, $campaign, $stream);
        $payload->setServerRequest($serverRequest);
        $payload->setResponse($response);
        return $payload;
    }
}

?>