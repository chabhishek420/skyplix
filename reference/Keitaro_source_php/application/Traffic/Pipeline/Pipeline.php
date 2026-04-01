<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline;

class Pipeline
{
    private $_stages = NULL;
    private $_repeats = NULL;
    private $_stagesFrozen = false;
    const LIMIT = 10;
    public function firstLevelStages()
    {
        $this->setStages([new Stage\DomainRedirectStage(), new Stage\CheckPrefetchStage(), new Stage\BuildRawClickStage(), new Stage\FindCampaignStage(), new Stage\CheckDefaultCampaignStage(), new Stage\UpdateRawClickStage(), new Stage\CheckParamAliasesStage(), new Stage\UpdateCampaignUniquenessSessionStage(), new Stage\ChooseStreamStage(), new Stage\UpdateStreamUniquenessSessionStage(), new Stage\ChooseLandingStage(), new Stage\ChooseOfferStage(), new Stage\GenerateTokenStage(), new Stage\FindAffiliateNetworkStage(), new Stage\UpdateHitLimitStage(), new Stage\UpdateCostsStage(), new Stage\UpdatePayoutStage(), new Stage\SaveUniquenessSessionStage(), new Stage\SetCookieStage(), new Stage\ExecuteActionStage(), new Stage\PrepareRawClickToStoreStage(), new Stage\CheckSendingToAnotherCampaign(), new Stage\StoreRawClicksStage()]);
        return $this;
    }
    public function secondLevelStages()
    {
        $this->setStages([new Stage\FindCampaignStage(), new Stage\UpdateParamsFromLandingStage(), new Stage\CheckDefaultCampaignStage(), new Stage\CheckParamAliasesStage(), new Stage\ChooseStreamStage(), new Stage\ChooseOfferStage(), new Stage\FindAffiliateNetworkStage(), new Stage\UpdateCostsStage(), new Stage\UpdatePayoutStage(), new Stage\SetCookieStage(), new Stage\ExecuteActionStage(), new Stage\CheckSendingToAnotherCampaign(), new Stage\StoreRawClicksStage()]);
        return $this;
    }
    public function freezeStages()
    {
        $this->_stagesFrozen = true;
    }
    public function setStages($stages)
    {
        $this->_stages = $stages;
    }
    public function start(Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        if (empty($this->_stages)) {
            throw new Stage\StageException("No stages set");
        }
        $logEntry->add("Starting pipeline");
        $logEntry->startProfiling();
        $payload = $this->_run($payload, $logEntry);
        $logEntry->stopProfiling("Pipeline execution time");
        return $payload;
    }
    private function _run(Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        foreach ($this->_stages as $stage) {
            $payload = $stage->process($payload, $logEntry);
            if (empty($payload)) {
                throw new Stage\StageException(get_class($stage) . " doesn't return payload");
            }
            if (!$payload->getServerRequest()) {
                throw new \Exception(get_class($stage) . " set serverRequest as null");
            }
            if (!$payload->getResponse()) {
                throw new \Exception(get_class($stage) . " set response as null");
            }
            if ($payload->isAborted()) {
                if ($payload->isAborted() && $payload->getForcedCampaignId()) {
                    if (!$this->_stagesFrozen) {
                        $this->firstLevelStages();
                    }
                    $this->_repeats++;
                    if ($this->_repeats < LIMIT) {
                        $payload = $this->_preparePayloadForCampaign($payload);
                        return $this->_run($payload, $logEntry);
                    }
                    $msg = "Stream #" . ($payload->getStream() ? $payload->getStream()->getId() : "X") . " . in campaign \"" . ($payload->getCampaign() ? $payload->getCampaign()->getId() : "") . "\" makes infinite recursion. Aborting.";
                    $logEntry->add($msg);
                    throw new Stage\StageException($msg);
                }
                return $payload;
            }
        }
    }
    private function _preparePayloadForCampaign(Payload $payload)
    {
        $rawClick = $payload->getRawClick();
        if (empty($rawClick)) {
            throw new Stage\StageException("rawClick is empty");
        }
        $nextRawClick = new \Traffic\RawClick($rawClick->getData());
        if ($payload->getCampaign()) {
            $nextRawClick->setParentCampaignId($payload->getCampaign()->getId());
        }
        $nextRawClick->setParentSubId($rawClick->getSubId());
        $payload->setCampaign(NULL);
        $payload->setOffer(NULL);
        $payload->setLanding(NULL);
        $payload->setStream(NULL);
        $payload->setLanding(NULL);
        $payload->setActionPayload(NULL);
        $payload->setActionType(NULL);
        $payload->setActionOptions(NULL);
        $payload->setRawClick($nextRawClick);
        $payload->setForcedStreamId(NULL);
        $payload->abort(false);
        return $payload;
    }
}

?>