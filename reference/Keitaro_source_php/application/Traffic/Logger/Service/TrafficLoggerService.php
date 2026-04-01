<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Logging\Service;

class TrafficLoggerService extends LoggerService
{
    private $_entry = NULL;
    public function __construct($dir = NULL)
    {
        parent::__construct($dir);
        $this->_entry = new \Traffic\Logging\NullTrafficLogEntry();
    }
    public function setEntry(\Traffic\Logging\TrafficLogEntry $entry)
    {
        $this->_entry = $entry;
    }
    public function flush()
    {
        $lines = $this->_entry->flush();
        if (count($lines)) {
            $this->info(implode("\t ", $lines));
        }
    }
    public function entry()
    {
        return $this->_entry;
    }
    protected function _getLogLevel()
    {
        return \Monolog\Logger::INFO;
    }
    protected function _getDefaultLogFileName()
    {
        return "traffic";
    }
    protected function _getLogFormat()
    {
        return \Core\Logging\Service\LogParserService::TRAFFIC_FORMAT;
    }
    protected function _getLogFormatter()
    {
        return $this->_getSimpleLogFormatter();
    }
}

?>