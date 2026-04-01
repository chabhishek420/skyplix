<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback\Stages;

class SendPostbacksStage implements \Component\Postback\ProcessPostback\StageInterface
{
    private $_campaign = NULL;
    private $_conversion = NULL;
    public function setConversion(\Traffic\Model\Conversion $conversion)
    {
        $this->_conversion = $conversion;
    }
    public function process(\Component\Postback\ProcessPostback\Payload $payload)
    {
        if ($payload->getPostback()->isIgnore()) {
            return NULL;
        }
        $this->_conversion = $payload->getConversionToSave();
        $this->_campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($this->_conversion->getCampaignId());
        if (empty($this->_campaign)) {
            throw new \Core\Application\Exception\Error("Campaign #" . $this->_conversion->getCampaignId() . " not found");
        }
        $trafficSource = $this->_findTS($this->_campaign);
        $this->_processCampaignPostbacks($this->_campaign);
        if (!empty($trafficSource)) {
            $this->_processTrafficSourcePostbacks($this->_campaign, $trafficSource);
        }
    }
    private function _findTS(\Traffic\Model\Campaign $campaign)
    {
        $tsId = $campaign->getTrafficSourceId();
        if (empty($tsId)) {
            return NULL;
        }
        return \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->find($tsId);
    }
    private function _processTrafficSourcePostbacks(\Traffic\Model\Campaign $campaign, \Traffic\Model\TrafficSource $trafficSource)
    {
        if (empty($trafficSource)) {
            return NULL;
        }
        $postbackUrl = $trafficSource->getPostback();
        if (!empty($postbackUrl)) {
            $this->_log("Campaign #" . $campaign->getId() . " has source postback");
            if ($this->_shouldBeSent($trafficSource->getPostbackStatuses())) {
                $this->_send($postbackUrl, "GET");
            }
        }
    }
    private function _processCampaignPostbacks(\Traffic\Model\Campaign $campaign)
    {
        $postbacks = \Component\Campaigns\Repository\CampaignPostbackRepository::instance()->getCampaignPostbacks($campaign);
        if (!count($postbacks)) {
            return NULL;
        }
        $this->_log("Campaign #" . $campaign->getId() . " has " . count($postbacks) . " postbacks");
        foreach ($postbacks as $postback) {
            if ($this->_shouldBeSent($postback->getStatuses())) {
                $this->_send($postback->getUrl(), $postback->getMethod());
            }
        }
    }
    private function _shouldBeSent($statuses)
    {
        if (is_array($statuses) && in_array($this->_conversion->getStatus(), $statuses)) {
            return true;
        }
        $this->_log("Incorrect status. Next campaign.");
        return false;
    }
    private function _send($url, $method)
    {
        $this->_log("Preparing \"" . $url . "\"; Sub_id: " . $this->_conversion->get("sub_id"));
        $pageContext = new \Core\Sandbox\SandboxContext(["server_request" => \Traffic\Request\ServerRequest::build(), "raw_click" => $this->_getRawClick($this->_conversion->get("sub_id")), "campaign" => $this->_getCampaign(), "stream" => $this->_getStream($this->_conversion->get("stream_id")), "conversion" => $this->_conversion]);
        try {
            $url = \Traffic\Macros\MacrosProcessor::process($pageContext, $url);
        } catch (\Exception $e) {
            $this->_log("Failed processing macro for s2s postbacks: " . $e->getMessage());
            $this->_httpSend($method, $url);
        }
    }
    private function _getCampaign()
    {
        return $this->_campaign;
    }
    private function _getStream($streamId)
    {
        try {
            return \Component\Streams\Repository\StreamRepository::instance()->find($streamId);
        } catch (\Core\Exceptions\NotFoundError $e) {
        }
    }
    private function _getRawClick($subId)
    {
        return \Component\Clicks\Repository\RawClickRepository::instance()->findBySubId($subId);
    }
    private function _httpSend($method, $url)
    {
        $this->_log("Send \"" . $url . "\"");
        try {
            $request = new \GuzzleHttp\Psr7\Request($method, $url);
            $timeout = \Traffic\Repository\CachedSettingsRepository::instance()->get("s2s_timeout", 5);
            if (empty($timeout) || !is_numeric($timeout)) {
                $timeout = 5;
            }
            $timeout = min($timeout, 15);
            $timeout = max($timeout, 1);
            $body = \Traffic\Http\Service\HttpService::instance()->send($request, [\GuzzleHttp\RequestOptions::TIMEOUT => $timeout])->getBody();
            if (preg_match("/[\\x00-\\x1F\\x7F]/u", $body)) {
                $this->_log("Response: <binary>");
            } else {
                $this->_log("Response: " . $body);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $this->_log("Response error: " . $e->getMessage() . " (code: " . $e->getCode());
        }
    }
    private function _log($msg)
    {
        \Core\Logging\Service\SentPostbackLoggerService::instance()->log($msg);
    }
}

?>