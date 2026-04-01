<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Av\Service;

class AVCheckerService extends \Traffic\Service\AbstractService
{
    private $_service = NULL;
    const AVSCAN = "avscan";
    const VCM = "vcm";
    const PAGE = "page";
    const DOMAIN = "domain";
    const REMOTE = "remote";
    public function getServices()
    {
        return [["value" => AVSCAN, "name" => "AVScan"], ["value" => VCM, "name" => "VirusCheckMate"]];
    }
    public function getService()
    {
        if (!$this->_service) {
            if (\Traffic\Repository\CachedSettingsRepository::instance()->get("av_service", "avscan") == AVSCAN) {
                $this->_service = new \Component\Av\Avscan\Avscan();
            } else {
                $this->_service = new \Component\Av\VCM\Viruscheckmate();
            }
        }
        return $this->_service;
    }
    public function preload($triggers)
    {
        if (count($triggers) == 0) {
            return NULL;
        }
        if (method_exists($this->getService(), "preload")) {
            $this->getService()->preload($this->getTargets($triggers));
        }
    }
    public function isDetected(\Component\Triggers\Model\TriggerAssociation $trigger)
    {
        return $this->getService()->isDetected($trigger);
    }
    public function getError()
    {
        return $this->getService()->getError();
    }
    public function getWarnings()
    {
        return $this->getService()->getWarnings();
    }
    public static function getTargets($triggers)
    {
        $targets = [];
        foreach ($triggers as $trigger) {
            try {
                $stream = \Component\Streams\Repository\StreamRepository::instance()->find($trigger->getStreamId());
                if (($stream->isActive() || $trigger->isReverse()) && $trigger->getCondition() == \Component\Triggers\Model\TriggerAssociation::CONDITION_AV_DETECTED) {
                    $targets[$trigger->getId()] = \Component\Triggers\Repository\TriggersRepository::instance()->getInfos($trigger);
                }
            } catch (\Core\Exceptions\NotFoundError $e) {
            }
        }
        return $targets;
    }
}

?>