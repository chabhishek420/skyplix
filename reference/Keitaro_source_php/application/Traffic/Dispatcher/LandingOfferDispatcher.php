<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Dispatcher;

class LandingOfferDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    private $_rawClick = NULL;
    public function __construct(\Traffic\RawClick $rawClick)
    {
        if (empty($rawClick)) {
            throw new \Exception("rawclick is not provided");
        }
        $this->_rawClick = $rawClick;
    }
    public function getRawClick()
    {
        return $this->_rawClick;
    }
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        $response = \Traffic\Response\Response::build(["disable_cache" => true]);
        if ($request->getParam("offer_id")) {
            $this->_rawClick->setOfferId((int) $request->getParam("offer_id"));
        }
        $pipelinePayload = new \Traffic\Pipeline\Payload(["server_request" => $request, "response" => $response, "raw_click" => $this->_rawClick, "forced_stream_id" => $this->_rawClick->getStreamId(), "forced_campaign_id" => $this->_rawClick->getCampaignId(), "forced_offer_id" => $this->_rawClick->getOfferId(), "force_redirect_offer" => true]);
        $logEntry = \Traffic\Logging\Service\TrafficLoggerService::instance()->entry();
        try {
            $pipeline = new \Traffic\Pipeline\Pipeline();
            $pipelinePayload = $pipeline->secondLevelStages()->start($pipelinePayload, $logEntry);
            $rawClick = $pipelinePayload->getRawClick();
            try {
                if ($rawClick->getOfferId()) {
                    \Traffic\Command\DelayedCommand\UpdateClickCommand::saveLpClick($rawClick->getSubId(), $rawClick->getOfferId(), $request->getAllParams(), $rawClick->getLandingId());
                }
            } catch (\ADODB_Exception $e) {
                $logEntry->add($e->getMessage());
                \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
                $response = $pipelinePayload->getResponse();
                $logEntry->flush();
                $logEntry->logRawClick($rawClick, $request);
                return $response;
            }
        } catch (\Traffic\Pipeline\Stage\StageException $exception) {
            $logEntry->add($exception->getMessage());
            \Traffic\Logging\Service\LoggerService::instance()->error($exception->getMessage());
            $logEntry->flush();
            return $this->_getErrorResponse();
        }
    }
    private function _getErrorResponse()
    {
        return \Traffic\Response\ResponseFactory::build(["body" => "Sorry. Some internal problems. Please read System Log.", "status" => 501]);
    }
}

?>