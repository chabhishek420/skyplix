<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamEvents\Service;

class StreamEventService extends \Core\Entity\Service\EntityService
{
    const PRUNE_PERIOD = 30;
    public function definition()
    {
        return \Component\Streams\Model\StreamEvent::definition();
    }
    public function prune()
    {
        $sql = "DELETE FROM " . \Core\Db\Db::getPrefix() . "monitoring_history";
        $sql .= " WHERE date < \"" . date("Y-m-d", strtotime("-" . PRUNE_PERIOD . " days")) . "\"";
        \Core\Db\Db::instance()->execute($sql);
    }
    public function createStreamEvent($data)
    {
        if (empty($data["date"])) {
            $data["date"] = new \DateTime();
        }
        return self::create($data);
    }
    public function info(\Component\Triggers\Model\TriggerAssociation $trigger, $message)
    {
        return $this->createStreamEvent(["level" => \Component\Streams\Model\StreamEvent::INFO, "trigger_id" => $trigger->getId(), "stream_id" => $trigger->getStreamId(), "message" => $message, "state" => \Component\Streams\Model\StreamEvent::UNREAD]);
    }
    public function warning(\Component\Triggers\Model\TriggerAssociation $trigger, $message)
    {
        return $this->createStreamEvent(["level" => \Component\Streams\Model\StreamEvent::WARNING, "trigger_id" => $trigger->getId(), "stream_id" => $trigger->getStreamId(), "message" => $message, "state" => \Component\Streams\Model\StreamEvent::UNREAD]);
    }
    public function error(\Component\Triggers\Model\TriggerAssociation $trigger, $message)
    {
        return $this->warning($trigger, $message);
    }
}

?>