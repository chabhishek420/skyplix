<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers\Service;

class TriggerService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Triggers\Model\TriggerAssociation::definition();
    }
    public function createTrigger(\Traffic\Model\BaseStream $stream, $data)
    {
        unset($data["oid"]);
        unset($data["id"]);
        $data["stream_id"] = $stream->getId();
        return $this->create($data);
    }
    public function deleteByStream(\Traffic\Model\BaseStream $stream)
    {
        $this->directDeleteAll("stream_id = " . $stream->getId());
    }
}

?>