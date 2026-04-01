<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class ChooseStreamStage implements StageInterface
{
    const NO_STREAM_SELECTED = "No stream selected";
    const SHOW_TEXT = "show_text";
    const DO_NOTHING = "do_nothing";
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $campaign = $payload->getCampaign();
        $rawClick = $payload->getRawClick();
        $serverRequest = $payload->getServerRequest();
        $response = $payload->getResponse();
        if (empty($response)) {
            throw new StageException("response is not set");
        }
        if (empty($serverRequest)) {
            throw new StageException("server_request is not defined");
        }
        if (empty($campaign)) {
            throw new StageException("campaign is not defined");
        }
        if (empty($rawClick)) {
            throw new StageException("rawClick is not defined");
        }
        $forcedStreamId = $payload->getForcedStreamId();
        if ($forcedStreamId) {
            try {
                $logEntry->add("Loading stream #" . $forcedStreamId);
                $stream = \Traffic\Repository\CachedStreamRepository::instance()->findCachedStream($forcedStreamId);
            } catch (\Core\Exceptions\NotFoundError $e) {
                $logEntry->add($e->getMessage());
                return $this->_triggerNotFound($payload, $logEntry);
            }
        }
        $groupedStreams = \Traffic\Repository\CachedStreamRepository::instance()->getCachedActiveStreams($campaign);
        if (empty($stream) || $stream->isDeleted()) {
            $logEntry->add("Processing campaign " . $campaign->getId());
            $streams = $groupedStreams->byType(\Traffic\Model\Stream::TYPE_FORCED);
            $rotator = new \Traffic\Actions\StreamRotator($campaign, $logEntry, $rawClick);
            $stream = $rotator->chooseByPosition($serverRequest, $streams);
        }
        if (empty($stream) || $stream->isDeleted()) {
            $streams = $groupedStreams->byType(\Traffic\Model\Stream::TYPE_REGULAR);
            $rotator = new \Traffic\Actions\StreamRotator($campaign, $logEntry, $rawClick);
            if ($campaign->getType() == \Traffic\Model\Campaign::TYPE_POSITION) {
                $stream = $rotator->chooseByPosition($serverRequest, $streams);
            } else {
                $stream = $rotator->chooseByWeight($serverRequest, $streams);
                if (!empty($stream) && $campaign->isBindVisitorsEnabled()) {
                    $rawClick = $payload->getRawClick();
                    $campaign = $payload->getCampaign();
                    $entityBinding = new \Traffic\Pipeline\Service\EntityBindingService($rawClick, $campaign, $logEntry);
                    $entityBinding->bindEntityRedis(\Traffic\Pipeline\Service\EntityBindingService::TYPE_STREAM_BINDING, $stream->getId());
                    $payload->enableCookieBindStream();
                }
            }
        }
        if (empty($stream) || $stream->isDeleted()) {
            $streams = $groupedStreams->byType(\Traffic\Model\Stream::TYPE_DEFAULT);
            $stream = empty($streams) ? NULL : $streams[0];
        }
        if (!empty($stream) && !$stream->isDeleted()) {
            if ($stream->getType() === \Traffic\Model\Stream::TYPE_DEFAULT) {
                $logEntry->add("Chosen default stream #" . $stream->getId());
            }
            $payload->setStream($stream);
            $payload->getRawClick()->setStreamId($stream->getId());
            if ($stream->getSchema() != \Traffic\Model\BaseStream::LANDINGS && $stream->getSchema() != \Traffic\Model\BaseStream::OFFERS) {
                $payload->setActionType($stream->getActionType());
                $payload->setActionPayload($stream->getActionPayload());
                $payload->setActionOptions($stream->getActionOptions());
            }
        } else {
            $this->_setNoDirection($payload);
        }
        return $payload;
    }
    private function _setNoDirection(\Traffic\Pipeline\Payload $payload)
    {
        $payload->setActionType(DO_NOTHING);
    }
    private function _triggerNotFound(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logger)
    {
        $response = $payload->getResponse()->withStatus(\Traffic\Response\StatusCode::NOT_FOUND)->withBody(\Traffic\Response\ResponseFactory::safeBody("Forced stream not found"));
        $logger->add("Forced stream not found. Shows 404 NotFound");
        $payload->setResponse($response);
        $payload->abort();
        return $payload;
    }
}

?>