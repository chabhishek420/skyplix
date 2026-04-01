<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers\Controller;

class TriggersController extends \Admin\Controller\BaseController
{
    public function updateAction()
    {
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($this->getParam("id"));
        $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($stream->getCampaignId());
        if (!$this->isEditAllowed($campaign)) {
            $this->throwDeny();
        }
        if ($this->isPost()) {
            \Component\Triggers\Service\StreamTriggerService::instance()->assign($stream, $this->getParam("triggers"));
            $triggers = \Component\Triggers\Repository\TriggersRepository::instance()->allByStream($stream);
            return $this->serialize($triggers, new \Component\Streams\Serializer\StreamTriggerSerializer());
        }
    }
    public function targetsAction()
    {
        return \Component\Triggers\Repository\TriggersRepository::getTargets();
    }
    public function conditionsAction()
    {
        return \Component\Triggers\Repository\TriggersRepository::getConditions();
    }
    public function actionsAction()
    {
        return \Component\Triggers\Repository\TriggersRepository::getActions();
    }
}

?>