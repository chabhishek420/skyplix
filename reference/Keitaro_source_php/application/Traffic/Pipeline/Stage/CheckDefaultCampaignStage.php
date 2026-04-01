<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class CheckDefaultCampaignStage implements StageInterface
{
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        if ($payload->getCampaign()) {
            return $payload;
        }
        $logEntry->add("Check default action from settings");
        $request = $payload->getServerRequest();
        if (empty($request)) {
            throw new StageException("Empty request");
        }
        $result = NULL;
        \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::EXTRA_ACTION);
        switch (\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::EXTRA_ACTION)) {
            case \Traffic\Model\Setting::EXTRA_ACTION_PARAM_CAMPAIGN:
                $payload = $this->_triggerCampaign($payload, $logEntry);
                break;
            case \Traffic\Model\Setting::EXTRA_ACTION_PARAM_REDIRECT:
                $payload = $this->_triggerRedirect($payload, $logEntry);
                break;
            default:
                $payload = $this->_triggerNotFound($payload, $logEntry);
                return $payload;
        }
    }
    private function _triggerRedirect(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logger)
    {
        $extraUrl = \Traffic\Repository\CachedSettingsRepository::instance()->get("extra_url");
        $logger->add("Redirecting to " . $extraUrl);
        $response = $payload->getResponse()->withStatus(\Traffic\Response\StatusCode::MOVED_TEMPORARILY)->withHeader("Location", $extraUrl);
        $payload->setResponse($response);
        $payload->abort();
        return $payload;
    }
    private function _triggerNotFound(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logger)
    {
        $response = $payload->getResponse()->withStatus(\Traffic\Response\StatusCode::NOT_FOUND)->withBody(\Traffic\Response\ResponseFactory::safeBody("404 Not Found"));
        $logger->add("Shows 404 NotFound");
        $payload->setResponse($response);
        $payload->abort();
        return $payload;
    }
    private function _triggerCampaign(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logger)
    {
        $id = \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::EXTRA_CAMPAIGN);
        $logger->add("Sending to default campaign #" . $id . "");
        $campaign = \Traffic\Repository\CachedCampaignRepository::instance()->findInCacheById($id);
        if (empty($campaign)) {
            throw new StageException("Default campaign missing. Check default action settings");
        }
        if (!$campaign->isActive()) {
            $logger->add("Default campaign #" . $id . " is not active, redirecting to 404");
            return $this->_triggerNotFound($payload, $logger);
        }
        $payload->setForcedCampaignId($id);
        $payload->abort();
        return $payload;
    }
}

?>