<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Model;

class StreamEvent extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "monitoring_history";
    const INFO = "info";
    const WARNING = "warning";
    const DEFAULT_LIMIT = 50;
    const READ = "read";
    const UNREAD = "unread";
    public static function validator()
    {
        return new \Component\Streams\Validator\StreamEventValidator();
    }
    public static function repository()
    {
        return \Component\Streams\Repository\StreamEventsRepository::instance();
    }
    public static function service()
    {
        return \Component\StreamEvents\Service\StreamEventService::instance();
    }
    public function getId()
    {
        return $this->get("id");
    }
    public function getTriggerId()
    {
        return $this->get("trigger_id");
    }
    public function getDate()
    {
        return $this->get("date");
    }
    public function getMessage()
    {
        return $this->get("message");
    }
    public function getLevel()
    {
        return $this->get("level");
    }
}

?>