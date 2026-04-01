<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Controller;

class BotlistController extends \Admin\Controller\BaseController
{
    public function getBotListCountAction()
    {
        return ["count" => \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getRepository()->getListCount()];
    }
    public function getBotListAction()
    {
        return ["value" => \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getRepository()->getList()];
    }
    public function saveBotListAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getService()->saveList($this->getPostParam("value"));
        return $this->getBotListCountAction();
    }
    public function addBotListAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getService()->addToList($this->getPostParam("value"));
        return $this->getBotListCountAction();
    }
    public function excludeBotListAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getService()->excludeFromList($this->getPostParam("value"));
        return $this->getBotListCountAction();
    }
    public function clearBotListAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        \Component\BotDetection\Repository\UserBotsLegacyRepository::instance()->clearList();
        \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getService()->cleanList();
        return $this->getBotListCountAction();
    }
    public function getBotSignatureCountAction()
    {
        return ["count" => \Component\BotDetection\Service\UserBotSignatureService::instance()->getAdditionalListCount()];
    }
    public function getBotSignatureAction()
    {
        return ["value" => \Component\BotDetection\Service\UserBotSignatureService::instance()->getAdditionalList()];
    }
    public function saveBotSignatureAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        \Component\BotDetection\Service\UserBotSignatureService::instance()->saveAdditionalList($this->getPostParam("value"));
        return $this->getBotSignatureCountAction();
    }
}

?>