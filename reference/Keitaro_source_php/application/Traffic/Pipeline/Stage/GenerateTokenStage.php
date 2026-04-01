<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class GenerateTokenStage implements StageInterface
{
    const TOKEN_PARAM = "_token";
    const SUBID_PARAM = "_subid";
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        $rawClick = $payload->getRawClick();
        $stream = $payload->getStream();
        if (!$payload->isTokenNeeded()) {
            $logEntry->add("Token is not needed. ");
            return $payload;
        }
        if ($payload->getRawClick()->getToken()) {
            return $payload;
        }
        if (empty($rawClick)) {
            throw new StageException("GenerateTokenStage: Empty rawClick");
        }
        if (empty($serverRequest)) {
            throw new StageException("serverRequest is not set");
        }
        if (empty($response)) {
            throw new StageException("response is not set");
        }
        if (empty($stream)) {
            return $payload;
        }
        $offers = \Traffic\Repository\CachedStreamOfferAssociationRepository::instance()->getCachedByStream($stream);
        if (empty($offers)) {
            $logEntry->add("Token is not needed because the stream does not contain any offers");
            return $payload;
        }
        $payload->enableSaveToken();
        $token = \Traffic\LpToken\Service\LpTokenService::instance()->storeRawClick($rawClick);
        if ($payload->shouldAddTokenToURL() && \Traffic\Actions\Repository\StreamActionRepository::instance()->isRedirect($payload->getActionType())) {
            $newActionPayload = $payload->getActionPayload();
            $subidParam = SUBID_PARAM . "=" . $payload->getRawClick()->getSubId();
            $tokenParam = TOKEN_PARAM . "=" . $token;
            $newActionPayload = \Traffic\Service\UrlService::instance()->addParameterToUrl($newActionPayload, $subidParam);
            $newActionPayload = \Traffic\Service\UrlService::instance()->addParameterToUrl($newActionPayload, $tokenParam);
            $payload->setActionPayload($newActionPayload);
        }
        $payload->getRawClick()->setToken($token);
        return $payload;
    }
}

?>