<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers\Service;

class StreamTriggerService extends \Traffic\Service\AbstractService
{
    public function deleteByStream(\Traffic\Model\BaseStream $stream, $exclude = [])
    {
        if (!count($exclude)) {
            $exclude = [-1];
        }
        $where = "stream_id = " . \Core\Db\Db::quote($stream->getId()) . " AND id NOT IN (" . implode(",", $exclude) . ")";
        TriggerService::instance()->directDeleteAll($where);
    }
    public function assign(\Traffic\Model\BaseStream $stream, $items)
    {
        if (!$stream->getId()) {
            throw new \Core\Application\Exception\Error("Stream is not created");
        }
        $exclude = [];
        if (isset($items) && count($items)) {
            foreach ($items as $data) {
                $trigger = NULL;
                if (!empty($data["id"])) {
                    $trigger = \Component\Triggers\Repository\TriggersRepository::instance()->find($data["id"]);
                }
                if (!isset($trigger)) {
                    $trigger = TriggerService::instance()->createTrigger($stream, $data);
                } else {
                    TriggerService::instance()->update($trigger, $data);
                }
                $exclude[] = $trigger->getId();
            }
        }
        $this->deleteByStream($stream, $exclude);
        return $this;
    }
}

?>