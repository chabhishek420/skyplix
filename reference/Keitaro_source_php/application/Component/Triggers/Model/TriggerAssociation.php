<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers\Model;

class TriggerAssociation extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected $_stream = NULL;
    public static $_fields = NULL;
    protected static $_tableName = "triggers";
    const ACTION_DISABLE = "disable";
    const ACTION_REPLACE_URL = "replace_url";
    const ACTION_GRAB_FROM_PAGE = "grab_from_page";
    const DO_NOTHING = "do_nothing";
    const WEBHOOK = "webhook";
    const TARGET_STREAM = "stream";
    const TARGET_LANDINGS = "landings";
    const TARGET_OFFERS = "offers";
    const TARGET_SELECTED_PAGE = "selected_page";
    const CONDITION_NOT_RESPOND = "not_respond";
    const CONDITION_ALWAYS = "always";
    const CONDITION_CONTAINS = "contains";
    const CONDITION_NOT_CONTAINS = "not_contains";
    const CONDITION_AV_DETECTED = "av_detected";
    const DEFAULT_INTERVAL = 30;
    public static function validator()
    {
        return new \Component\Triggers\Validator\TriggerValidator();
    }
    public static function repository()
    {
        return \Component\Triggers\Repository\TriggersRepository::instance();
    }
    public static function service()
    {
        return \Component\Triggers\Service\TriggerService::instance();
    }
    public function getStreamId()
    {
        return $this->get("stream_id");
    }
    public function setStreamId($value)
    {
        $this->set("stream_id", (int) $value);
        return $this;
    }
    public function setCheckText($value)
    {
        $this->set("check_text", $value);
        return $this;
    }
    public function getCheckText()
    {
        return $this->get("check_text");
    }
    public function getAction()
    {
        return $this->get("action");
    }
    public function shouldScanPage()
    {
        return $this->get("scan_page") == 1;
    }
    public function getCondition()
    {
        return $this->get("condition");
    }
    public function setTarget($value)
    {
        if (!in_array($value, self::$_validTargets)) {
            throw new \Exception("Target \"" . $value . "\" not allowed");
        }
        $this->set("target", $value);
        return $this;
    }
    public function getTarget()
    {
        return $this->get("target");
    }
    public function getSelectedPage()
    {
        return $this->get("selected_page");
    }
    public function setSelectedPage($value)
    {
        $this->set("selected_page", $value);
        return $this;
    }
    public function setPattern($value)
    {
        $this->set("pattern", $value);
        return $this;
    }
    public function getPattern()
    {
        return $this->get("pattern");
    }
    public function setInterval($value)
    {
        $this->set("interval", (int) $value);
    }
    public function getInterval()
    {
        return $this->get("interval");
    }
    public function setNextRunAt($value)
    {
        $this->set("next_run_at", $value);
        return $this;
    }
    public function getNextRunAt()
    {
        return $this->get("next_run_at");
    }
    public function setAlternativeUrls($value)
    {
        $this->set("alternative_urls", $value);
        return $this;
    }
    public function getAlternativeUrls()
    {
        return $this->get("alternative_urls");
    }
    public function getAlternativeUrlsAsArray()
    {
        return explode("\n", $this->get("alternative_urls"));
    }
    public function setGrabFromPage($value)
    {
        $this->set("grab_from_page", $value);
        return $this;
    }
    public function getGrabFromPage()
    {
        return $this->get("grab_from_page");
    }
    public function isReverse()
    {
        return $this->getReverse();
    }
    public function getReverse()
    {
        return $this->get("reverse");
    }
    public function isTargetStream()
    {
        return $this->getTarget() == TARGET_STREAM;
    }
    public function isTargetOffer()
    {
        return $this->getTarget() == TARGET_OFFERS;
    }
    public function isTargetSelectedPage()
    {
        return $this->getTarget() == TARGET_SELECTED_PAGE;
    }
    public function isTargetLanding()
    {
        return $this->getTarget() == TARGET_LANDINGS;
    }
}

?>