<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters;

class CheckFilters
{
    private $_serverRequest = NULL;
    private $_rawClick = NULL;
    private $_stream = NULL;
    private $_logEntry = NULL;
    public function __construct(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $this->_serverRequest = $serverRequest;
        $this->_stream = $stream;
        $this->_rawClick = $rawClick;
        $this->_logEntry = $logEntry;
    }
    public function isPass()
    {
        $this->_logEntry->add("Checking stream #" . $this->_stream->getId());
        $filters = \Traffic\Repository\CachedStreamFilterRepository::instance()->allCached($this->_stream);
        if (empty($filters)) {
            $this->_logEntry->add("Stream #" . $this->_stream->getId() . " contains no filters. Passed.");
            return true;
        }
        $blockedOrFilters = [];
        foreach ($filters as $filterData) {
            $filter = Repository\FilterRepository::instance()->getFilter($filterData->getName());
            $filter->setServerRequest($this->_serverRequest);
            $filter->setLogger($this->_logEntry);
            if (!$filter->isPass($filterData, $this->_rawClick)) {
                if (!$this->_stream->isFilterOr()) {
                    $this->_logEntry->add("Blocks by filter \"" . $filterData->getName() . "\". Not passed.");
                    return false;
                }
                $blockedOrFilters[] = $filter->getKey();
            } else {
                if ($this->_stream->isFilterOr()) {
                    $filterName = $filterData->getName();
                    $payload = $filterData->getPayload();
                    $msg = is_array($payload) && !empty($payload["name"]) ? "Accepts by filter \"" . $filterName . "\" by parameter name: \"" . $payload["name"] . "\". Passed." : "Accepts by filter \"" . $filterName . "\". Passed.";
                    $this->_logEntry->add($msg);
                    return true;
                }
            }
        }
        if ($this->_stream->isFilterOr()) {
            $this->_logEntry->add("Deny by all the filters: " . implode(", ", $blockedOrFilters) . ". Not passed.");
            return false;
        }
        $this->_logEntry->add("All filters are checked. Passed.");
        return true;
    }
}

?>