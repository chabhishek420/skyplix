<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Profiler;

class ProfilerService extends \Traffic\Service\AbstractService
{
    private $_profiler = NULL;
    public function __construct()
    {
        $this->_profiler = new Profiler();
    }
    public function getProfiler()
    {
        return $this->_profiler;
    }
    public function step($label, $requests = NULL)
    {
        return $this->_profiler->step($label, $requests);
    }
    public function enableXhprof()
    {
        $this->_profiler->enableXhprof();
    }
    public function disableXhprof()
    {
        $this->_profiler->disableXhprof();
    }
    public function resetState()
    {
        $this->_profiler->resetState();
    }
}

?>