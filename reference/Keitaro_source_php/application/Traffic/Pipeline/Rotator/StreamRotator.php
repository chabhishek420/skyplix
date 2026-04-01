<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions;

class StreamRotator
{
    private $_logEntry = NULL;
    private $_rawClick = NULL;
    private $_campaign = NULL;
    private $_bindingEntityType = NULL;
    private $_entityBindingService = NULL;
    public function __construct(\Traffic\Model\Campaign $campaign, \Traffic\Logging\TrafficLogEntry $logEntry, \Traffic\RawClick $rawClick)
    {
        $this->_campaign = $campaign;
        $this->_rawClick = $rawClick;
        $this->_logEntry = $logEntry;
        $this->_bindingEntityType = \Traffic\Pipeline\Service\EntityBindingService::TYPE_STREAM_BINDING;
        $this->_entityBindingService = new \Traffic\Pipeline\Service\EntityBindingService($this->_rawClick, $this->_campaign, $this->_logEntry);
    }
    public function chooseByPosition(\Traffic\Request\ServerRequest $serverRequest, $streams)
    {
        for ($i = 0; $i <= count($streams) - 1; $i++) {
            $stream = $streams[$i];
            $checkFilter = new \Component\StreamFilters\CheckFilters($serverRequest, $stream, $this->_rawClick, $this->_logEntry);
            if ($checkFilter->isPass()) {
                $this->_logEntry->add("Passed. Checking the schema and action.");
                return $stream;
            }
        }
    }
    public function chooseByWeight(\Traffic\Request\ServerRequest $serverRequest, $streams)
    {
        if ($this->_campaign->isBindVisitorsEnabled()) {
            $stream = $this->_findBoundStream($serverRequest, $streams);
        }
        if (empty($stream)) {
            $stream = $this->_rollDice($serverRequest, $streams);
        }
        return $stream;
    }
    protected function _rollDice(\Traffic\Request\ServerRequest $serverRequest, $streams)
    {
        if (!count($streams)) {
            return NULL;
        }
        shuffle($streams);
        $totalWeight = 0;
        foreach ($streams as $i => $stream) {
            $weight = $stream->getWeight();
            $totalWeight += $weight;
        }
        if ($totalWeight == 0) {
            return NULL;
        }
        $rand = mt_rand(0, $totalWeight - 1);
        $currentWeight = 0;
        $selected = 0;
        foreach ($streams as $i => $stream) {
            $weight = $stream->getWeight();
            if ($currentWeight <= $rand && $rand < $currentWeight + $weight) {
                $stream = $streams[$selected];
                $checkFilter = new \Component\StreamFilters\CheckFilters($serverRequest, $stream, $this->_rawClick, $this->_logEntry);
                if ($checkFilter->isPass()) {
                    return $stream;
                }
                unset($streams[$selected]);
                return $this->_rollDice($serverRequest, $streams);
            }
            $currentWeight += $weight;
            $selected++;
        }
    }
    private function _findBoundStream(\Traffic\Request\ServerRequest $serverRequest, $streams)
    {
        $streamId = $this->_entityBindingService->findBoundEntity($serverRequest, $this->_bindingEntityType);
        if (!empty($streamId)) {
            foreach ($streams as $stream) {
                if ($stream->getId() == $streamId) {
                    return $stream;
                }
            }
        }
        return NULL;
    }
}

?>