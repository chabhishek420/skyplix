<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class ClickApiDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    private $_version = NULL;
    private $_pipelinePayload = NULL;
    public function __construct(\Traffic\Pipeline\Payload $payload, $version = 1)
    {
        $this->_pipelinePayload = $payload;
        $this->_version = $version;
    }
    public function setVersion($version)
    {
        $this->_version = $version;
    }
    public function getPipelinePayload()
    {
        return $this->_pipelinePayload;
    }
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $pipelinePayload = $this->getPipelinePayload();
        $pipelinePayload->setResponse(\Traffic\Response\Response::build());
        $logEntry = \Traffic\Logging\Service\TrafficLoggerService::instance()->entry();
        if ($this->_mustReturnLog($request)) {
            $logEntry = new \Traffic\Logging\TrafficLogEntry();
        }
        try {
            $pipeline = new \Traffic\Pipeline\Pipeline();
            $pipeline->firstLevelStages();
            $pipelinePayload = $pipeline->start($pipelinePayload, $logEntry);
            $logEntry->logRawClick($pipelinePayload->getRawClick(), $pipelinePayload->getServerRequest());
            switch ($this->_version) {
                case 1:
                    $json = $this->_forVersion1($pipelinePayload);
                    break;
                case 2:
                    $json = $this->_forVersion2($pipelinePayload);
                    break;
                case 3:
                    $json = $this->_forVersion3($pipelinePayload);
                    if ($this->_mustReturnLog($request)) {
                        $json["log"] = $logEntry->flush();
                    }
                    $response = \Traffic\Response\Response::buildJson()->withStatus(\Traffic\Response\StatusCode::OK)->withBody(\Traffic\Response\ResponseFactory::safeBody($json));
                    return $response;
                    break;
                default:
                    $msg = "Unimplemented API version: " . $this->_version;
                    $errorResponse = $this->_buildErrorResponse(403, $msg);
                    return $errorResponse;
            }
        } catch (\Traffic\Pipeline\Stage\StageException $exception) {
            $logEntry->add($exception->getMessage());
            return $this->_buildErrorResponse(500, $exception->getMessage());
        }
    }
    private function _buildErrorResponse($status, $error)
    {
        return \Traffic\Response\Response::buildJson(["status" => $status, "body" => ["error" => $error]]);
    }
    private function _forVersion1(\Traffic\Pipeline\Payload $payload)
    {
        $response = $payload->getResponse()->withoutHeader(\Traffic\Response\ContentType::HEADER);
        $json = [];
        if ($payload->getStream()) {
            $stream = $payload->getStream();
            $json["stream"] = ["id" => $stream->getId(), "url" => $payload->getActionPayload(), "type" => $payload->getActionType(), "campaign_id" => $payload->getCampaign()->getId()];
        }
        $filteredResponse = $response->withoutHeader("Set-Cookie");
        $json["redirect"] = ["headers" => \Core\ServerRenderer\ServerRenderer::headersToList($filteredResponse->getHeaders()), "content" => (int) $filteredResponse->getBody()];
        return $json;
    }
    private function _forVersion2(\Traffic\Pipeline\Payload $payload)
    {
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        $contentType = $response->getHeaderLine(\Traffic\Response\ContentType::HEADER);
        $response = $response->withoutHeader(\Traffic\Response\ContentType::HEADER);
        $filteredResponse = $response->withoutHeader("Set-Cookie");
        $json = ["body" => (int) $filteredResponse->getBody(), "headers" => \Core\ServerRenderer\ServerRenderer::headersToList($filteredResponse->getHeaders()), "status" => $response->getStatusCode(), "contentType" => $contentType, "uniqueness_cookie" => \Traffic\Cookies\Service\CookiesService::instance()->getRaw($serverRequest, \Traffic\Session\Storage\CookiesStorage::getCookieName())];
        if ($payload->getServerRequest()->getParam("info")) {
            $json["info"] = ["type" => $payload->getActionType(), "url" => $payload->getActionPayload(), "campaign_id" => $payload->getRawClick()->getCampaignId(), "stream_id" => $payload->getRawClick()->getStreamId(), "landing_id" => $payload->getRawClick()->getLandingId(), "sub_id" => $payload->getRawClick()->getSubId(), "is_bot" => $payload->getRawClick()->isBot(), "offer_id" => $payload->getRawClick()->getOfferId(), "token" => $payload->getRawClick()->getToken(), "uniqueness" => ["campaign" => $payload->getRawClick()->isUniqueCampaign(), "stream" => $payload->getRawClick()->isUniqueStream(), "global" => $payload->getRawClick()->isUniqueGlobal()]];
        }
        return $json;
    }
    private function _mustReturnLog(\Traffic\Request\ServerRequest $request)
    {
        return $request->getParam("log");
    }
    private function _forVersion3(\Traffic\Pipeline\Payload $payload)
    {
        $serverRequest = $payload->getServerRequest();
        $json = $this->_forVersion2($payload);
        if ($payload->getCampaign()) {
            $json["cookies_ttl"] = $payload->getCampaign()->getCookiesTtl();
        }
        $json["cookies"] = \Traffic\Cookies\Service\CookiesService::instance()->getAll($serverRequest);
        return $json;
    }
}

?>