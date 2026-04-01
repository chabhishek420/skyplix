<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers;

class CheckTrigger
{
    private $_trigger = NULL;
    private $_stream = NULL;
    const REQUEST_STATUS = "status";
    const REQUEST_BODY = "body";
    const DEFAULT_UA = "Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.5) Gecko/2008120122 Firefox/3.0.5";
    public function __construct(Model\TriggerAssociation $trigger)
    {
        $this->_trigger = $trigger;
        $this->_stream = \Component\Streams\Repository\StreamRepository::instance()->find($trigger->getStreamId());
    }
    public function check()
    {
        while (empty($this->_stream)) {
            try {
                if ($this->_checkCondition()) {
                    $this->_doAction();
                } else {
                    if ($this->_trigger->isReverse()) {
                        $this->_doReverseAction();
                    }
                }
            } catch (Exception\TriggerMinorError $e) {
                $this->_warning($e->getMessage() . " (" . \Core\Locale\LocaleService::t("triggers.error.skip") . ")");
                $next = time() + $this->_trigger->getInterval() * 60;
                $this->_trigger->setNextRunAt($next);
                Service\TriggerService::instance()->save($this->_trigger);
            }
        }
        Service\TriggerService::instance()->delete($this->_trigger);
    }
    private function _checkCondition()
    {
        $urls = $this->_findTargetUrls();
        $triggered = false;
        foreach ($urls as $request) {
            $this->_trigger->getCondition();
            switch ($this->_trigger->getCondition()) {
                case Model\TriggerAssociation::CONDITION_NOT_RESPOND:
                    $triggered = !$this->_isStatusOk($request);
                    break;
                case Model\TriggerAssociation::CONDITION_CONTAINS:
                    $triggered = $this->_isPageContentOk($request);
                    break;
                case Model\TriggerAssociation::CONDITION_NOT_CONTAINS:
                    $triggered = !$this->_isPageContentOk($request);
                    break;
                case Model\TriggerAssociation::CONDITION_AV_DETECTED:
                    $triggered = $this->_isAvDetected();
                    break;
                case Model\TriggerAssociation::CONDITION_ALWAYS:
                    $triggered = true;
                    if ($triggered) {
                        return $triggered;
                    }
                    break;
                default:
                    throw new \Core\Exception("Condition " . $this->_trigger->getCondition() . " not allowed");
            }
        }
        return $triggered;
    }
    private function _isStatusOk($url)
    {
        if (!empty($url)) {
            $httpCode = $this->_doRequest($url, REQUEST_STATUS);
            return $httpCode && $httpCode < 400;
        }
        return true;
    }
    private function _isPageContentOk($url)
    {
        $content = $this->_doRequest($url, REQUEST_BODY);
        $pattern = $this->_trigger->getPattern();
        if (empty($pattern)) {
            return false;
        }
        if (substr($pattern, -1, 1) == "/") {
            $pattern = $pattern . "uis";
            return @preg_match($pattern, $content);
        }
        return stristr($content, $pattern) ? true : false;
    }
    private function _doRequest($url, $get = self::REQUEST_STATUS)
    {
        try {
            $response = \Traffic\Http\Service\HttpService::instance()->get($url, ["headers" => ["UserAgent" => $this->_findUserAgent()]]);
            if ($get == REQUEST_STATUS) {
                return $response->getStatusCode();
            }
            return (int) $response->getBody();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            return NULL;
        } catch (\InvalidArgumentException $e) {
        }
    }
    private function _findTargetUrls()
    {
        $this->_trigger->getTarget();
        switch ($this->_trigger->getTarget()) {
            case Model\TriggerAssociation::TARGET_STREAM:
                return [$this->_stream->getActionPayload()];
                break;
            case Model\TriggerAssociation::TARGET_OFFERS:
                $urls = [];
                foreach (\Component\Streams\Repository\StreamOfferAssociationRepository::instance()->allByStream($this->_stream) as $association) {
                    $offer = \Component\Offers\Repository\OfferRepository::instance()->find($association->get("offer_id"));
                    $urls[] = \Component\Offers\Service\OfferService::instance()->addParameterToUrl($offer->getActionPayload(), "");
                }
                return $urls;
                break;
            case Model\TriggerAssociation::TARGET_LANDINGS:
                $urls = [];
                foreach (\Component\Streams\Repository\StreamLandingAssociationRepository::instance()->allByStream($this->_stream) as $association) {
                    $landing = \Component\Landings\Repository\LandingRepository::instance()->find($association->get("landing_id"));
                    $urls[] = $landing->getActionPayload();
                }
                return $urls;
                break;
            case Model\TriggerAssociation::TARGET_SELECTED_PAGE:
                return [$this->_trigger->getSelectedPage()];
                break;
            default:
                throw new Exception\TriggerMinorError("Target " . $this->_trigger->getTarget() . " not exists");
        }
    }
    private function _doAction()
    {
        $this->_trigger->getAction();
        switch ($this->_trigger->getAction()) {
            case Model\TriggerAssociation::ACTION_DISABLE:
                $this->_disableStream();
                break;
            case Model\TriggerAssociation::ACTION_REPLACE_URL:
                $this->_findAndReplaceUrl();
                break;
            case Model\TriggerAssociation::ACTION_GRAB_FROM_PAGE:
                $this->_grabAndReplace();
                break;
            case Model\TriggerAssociation::WEBHOOK:
                $this->_webhook();
                break;
            case Model\TriggerAssociation::DO_NOTHING:
            default:
                throw new Exception\TriggerMinorError("Action " . $this->_trigger->getAction() . " not allowed");
        }
    }
    private function _doReverseAction()
    {
        $this->_trigger->getAction();
        switch ($this->_trigger->getAction()) {
            case Model\TriggerAssociation::ACTION_DISABLE:
                $this->_enableStream();
                break;
        }
    }
    private function _grabUrlByChecker()
    {
        $request = $this->_trigger->getGrabFromPage();
        $content = strip_tags(trim($this->_doRequest($request, REQUEST_BODY)));
        return $content;
    }
    private function _grabAndReplace()
    {
        $oldUrl = $this->_stream->getActionPayload();
        $grabberUrl = $this->_grabUrlByChecker();
        $newUrl = $this->_mergeUrl($oldUrl, $grabberUrl);
        if ($newUrl != $this->_stream->getActionPayload()) {
            $this->_actionInfo(\Core\Locale\LocaleService::t("triggers.url_replaced_to", $oldUrl) . $newUrl);
            \Component\Streams\Service\StreamService::instance()->update($this->_stream, ["action_payload" => $newUrl]);
        }
    }
    private function _disableStream()
    {
        if ($this->_stream->isActive()) {
            $this->_actionInfo(\Core\Locale\LocaleService::t("triggers.stream_disabled", "utf-8"));
            \Component\Streams\Service\StreamService::instance()->disable($this->_stream);
            \Component\Campaigns\Service\CampaignService::instance()->resortStreams(\Component\Campaigns\Repository\CampaignRepository::instance()->find($this->_stream->getCampaignId()));
        }
    }
    private function _enableStream()
    {
        if (!$this->_stream->isActive()) {
            $this->_actionInfo(\Core\Locale\LocaleService::t("triggers.stream_enabled"), true);
            \Component\Streams\Service\StreamService::instance()->makeActive($this->_stream);
            \Component\Campaigns\Service\CampaignService::instance()->resortStreams(\Component\Campaigns\Repository\CampaignRepository::instance()->find($this->_stream->getCampaignId()));
        }
    }
    private function _findAndReplaceUrl()
    {
        if (strstr($this->_trigger->getAlternativeUrls(), ",")) {
            $urls = explode(",", $this->_trigger->getAlternativeUrls());
        } else {
            $urls = explode("\n", $this->_trigger->getAlternativeUrls());
        }
        $replaceToUrl = "";
        foreach ($urls as $index => $url) {
            $url = trim($url);
            $url = $this->_composeUrl($this->_stream->getActionPayload(), $url);
            if ($url != $this->_stream->getActionPayload() && $this->_isStatusOk($url)) {
                unset($urls[$index]);
                $replaceToUrl = $url;
                if (empty($replaceToUrl)) {
                    throw new Exception\TriggerMinorError(\Core\Locale\LocaleService::t("triggers.error.no_alternative_urls"));
                }
                if ($this->_stream->getSchema() === \Traffic\Model\Stream::REDIRECT) {
                    $this->_info(\Core\Locale\LocaleService::t("triggers.url_replaced_to", $this->_stream->getActionPayload()) . $replaceToUrl);
                    $this->_trigger->setAlternativeUrls(implode("\n", $urls));
                    Service\TriggerService::instance()->save($this->_trigger);
                    \Component\Streams\Service\StreamService::instance()->update($this->_stream, ["action_payload" => $replaceToUrl]);
                } else {
                    $this->_warning(\Core\Locale\LocaleService::t("triggers.url_replaced_block"));
                }
            }
        }
    }
    private function _composeUrl($oldUlr, $urlOrDomain)
    {
        if (!strstr($urlOrDomain, "http://") && !strstr($urlOrDomain, "https://")) {
            $parts = parse_url($oldUlr);
            $parts["host"] = $urlOrDomain;
            $url = $this->_buildUrl($parts);
        } else {
            $url = $urlOrDomain;
        }
        return $url;
    }
    private function _mergeUrl($oldUrl, $newUrl)
    {
        if (!trim($newUrl)) {
            return $oldUrl;
        }
        if (strstr($newUrl, "://")) {
            return $newUrl;
        }
        $newUrl = "http://" . $newUrl;
        $oldUrl = parse_url($oldUrl);
        $newUrl = parse_url($newUrl);
        $parts = array_merge($oldUrl, $newUrl);
        return $this->_buildUrl($parts);
    }
    private function _buildUrl($parts)
    {
        $url = $parts["scheme"] . "://" . $parts["host"];
        if (isset($parts["port"])) {
            $url .= ":" . $parts["port"];
        }
        $url .= isset($parts["path"]) ? $parts["path"] : "/";
        if (isset($parts["query"])) {
            $url .= "?" . $parts["query"];
        }
        return $url;
    }
    private function _actionInfo($message, $reverse = false)
    {
        $target = $this->_trigger->getTarget() == Model\TriggerAssociation::TARGET_STREAM ? \Core\Locale\LocaleService::t("triggers.target_stream") : \Core\Locale\LocaleService::t("triggers.target_selected_page");
        $this->_trigger->getCondition();
        switch ($this->_trigger->getCondition()) {
            case Model\TriggerAssociation::CONDITION_NOT_RESPOND:
                $str = $reverse ? mb_strtolower(\Core\Locale\LocaleService::t("triggers.condition_respond"), "utf-8") : mb_strtolower(\Core\Locale\LocaleService::t("triggers.condition_not_respond"), "utf-8");
                break;
            case Model\TriggerAssociation::CONDITION_CONTAINS:
                $str = mb_strtolower(\Core\Locale\LocaleService::t("triggers.condition_contains"), "utf-8") . " '" . $this->_trigger->getPattern() . "'";
                break;
            case Model\TriggerAssociation::CONDITION_NOT_CONTAINS:
                $str = mb_strtolower(\Core\Locale\LocaleService::t("triggers.condition_not_contains"), "utf-8") . " '" . $this->_trigger->getPattern() . "'";
                break;
            case Model\TriggerAssociation::CONDITION_AV_DETECTED:
                $str = mb_strtolower(\Core\Locale\LocaleService::t("triggers.condition_av_detected"), "utf-8");
                break;
            default:
                if (isset($str)) {
                    $prefix = $target . " " . $str . ". ";
                    $this->_info($prefix . $message);
                } else {
                    $this->_info($message);
                }
        }
    }
    private function _info($message)
    {
        \Component\StreamEvents\Service\StreamEventService::instance()->info($this->_trigger, $message);
    }
    private function _warning($message)
    {
        \Component\StreamEvents\Service\StreamEventService::instance()->warning($this->_trigger, $message);
    }
    private function _error($message)
    {
        \Component\StreamEvents\Service\StreamEventService::instance()->error($this->_trigger, $message);
    }
    private function _findUserAgent()
    {
        return \Traffic\Service\ConfigService::instance()->get("system", "monitoring_user_agent", DEFAULT_UA);
    }
    private function _isAvDetected()
    {
        if (\Component\Av\Service\AVCheckerService::instance()->getError()) {
            $this->_error("AV Error: " . \Component\Av\Service\AVCheckerService::instance()->getError());
            return false;
        }
        $infos = Repository\TriggersRepository::instance()->getInfos($this->_trigger);
        if ($this->_trigger->shouldScanPage()) {
            $domainOrUrl = implode(", ", array_pluck($infos, "domain"));
        } else {
            $domainOrUrl = implode(", ", array_pluck($infos, "domain"));
        }
        $detected = \Component\Av\Service\AVCheckerService::instance()->isDetected($this->_trigger);
        $detected = array_unique($detected);
        if (!count($detected)) {
            return false;
        }
        $this->_info(\Core\Locale\LocaleService::t("triggers.av_detected", $domainOrUrl) . implode(", ", $detected));
        return true;
    }
    private function _webhook()
    {
        $request = $this->_trigger->getGrabFromPage();
        $status = $this->_doRequest($request, REQUEST_STATUS);
        if ($status !== 200) {
            \Traffic\Logging\Service\LoggerService::instance()->error("WebHook error: stream " . $this->_trigger->getStreamId() . ". Status code: " . $status);
        }
    }
}

?>