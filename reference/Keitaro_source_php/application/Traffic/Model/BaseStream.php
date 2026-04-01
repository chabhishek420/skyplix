<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class BaseStream extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    use Mixin\StateMethodsTrait;
    protected static $_fields = NULL;
    protected static $_tableName = "streams";
    protected static $_cacheKey = "STREAM";
    protected static $_aclKey = NULL;
    protected static $_entityName = "stream";
    const ACTION = "action";
    const LANDINGS = "landings";
    const OFFERS = "offers";
    const REDIRECT = "redirect";
    const DEFAULT_SCHEMA = "redirect";
    public static function validator()
    {
        return new \Component\Streams\StreamValidator();
    }
    public static function repository()
    {
        return \Component\Streams\Repository\StreamRepository::instance();
    }
    public static function service()
    {
        return \Component\Streams\Service\StreamService::instance();
    }
    public function getName()
    {
        return $this->get("name");
    }
    public function getType()
    {
        return $this->get("type");
    }
    public function setPosition($value)
    {
        $this->set("position", (int) $value);
        return $this;
    }
    public function getPosition()
    {
        return $this->get("position");
    }
    public function getUpdatedAt()
    {
        return $this->get("updated_at");
    }
    public function setUrl($value)
    {
        $this->set("action_payload", $value);
        return $this;
    }
    public function setState($value)
    {
        $this->set("state", $value);
        return $this;
    }
    public function getComments()
    {
        return $this->get("comments");
    }
    public function getCampaignId()
    {
        return $this->get("campaign_id");
    }
    public function getActionOptions()
    {
        return $this->get("action_options");
    }
    public function getActionOption($name)
    {
        $options = $this->getActionOptions();
        if (isset($options[$name])) {
            return $options[$name];
        }
        return NULL;
    }
    public function setActionOptions($actionOptions)
    {
        return $this->set("action_options", $actionOptions);
    }
    public function getActionType()
    {
        return $this->get("action_type");
    }
    public function setActionType($actionType)
    {
        return $this->set("action_type", $actionType);
    }
    public function setActionPayload($value)
    {
        return $this->set("action_payload", $value);
    }
    public function getActionPayload()
    {
        return $this->get("action_payload");
    }
    public function getWeight()
    {
        return $this->get("weight");
    }
    public function isFilterOr()
    {
        return $this->get("filter_or");
    }
    public function getSchema()
    {
        if (!$this->get("schema")) {
            return DEFAULT_SCHEMA;
        }
        return $this->get("schema");
    }
}

?>