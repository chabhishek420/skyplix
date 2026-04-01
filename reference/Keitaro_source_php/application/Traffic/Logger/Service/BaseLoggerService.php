<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Logging\Service;

abstract class BaseLoggerService extends \Traffic\Service\AbstractService
{
    protected $_logger = NULL;
    protected $_jid = NULL;
    protected $_logDirectory = NULL;
    protected $_logFilename = NULL;
    protected $_contextName = NULL;
    const DATETIME_FORMAT = "Y-m-d H:i:s";
    protected abstract function _getLogLevel();
    protected abstract function _getDefaultLogFileName();
    protected abstract function _getLogFormat();
    protected abstract function _getLogFormatter();
    protected function _getLogFileName()
    {
        if (empty($this->_logFilename)) {
            return $this->_getDefaultLogFileName();
        }
        return $this->_logFilename;
    }
    protected function _getLogDirectory()
    {
        if (empty($this->_logDirectory)) {
            return ROOT . "/var/log";
        }
        return $this->_logDirectory;
    }
    protected function _getLogPath()
    {
        return $this->_getLogDirectory() . "/" . $this->_getLogFileName() . ".log";
    }
    protected function _getSystemLogFormatter()
    {
        $jid = $this->getJid();
        $dateFormat = DATETIME_FORMAT;
        $output = "[%datetime%] [%level_name%] (jid:" . $jid . ") %context% : %message% \n";
        return new \Monolog\Formatter\LineFormatter($output, $dateFormat);
    }
    protected function _getSimpleLogFormatter()
    {
        $jid = $this->getJid();
        $dateFormat = DATETIME_FORMAT;
        $output = "[%datetime%] (jid:" . $jid . ") : %message% \n";
        return new \Monolog\Formatter\LineFormatter($output, $dateFormat);
    }
    public function getJid()
    {
        if (!isset($this->_jid)) {
            $this->_jid = uniqid();
        }
        return $this->_jid;
    }
    public function setLogDirectory($logDirectory)
    {
        $this->_logDirectory = $logDirectory;
    }
    public function setLogFilename($filename)
    {
        $this->_logFilename = $filename;
        $stream = new \Monolog\Handler\StreamHandler($this->_getLogPath(), \Monolog\Logger::DEBUG);
        $stream->setFormatter($this->_getLogFormatter());
        $this->_logger->setHandlers([$stream]);
    }
    public function cleanLog()
    {
        \Core\Logging\Service\LogParserService::instance()->cleanLog($this->_getLogDirectory(), $this->_getLogFileName());
    }
    public function getRows($offset, $limit, \DateTimeZone $tz = NULL, $query = NULL)
    {
        $entries = \Core\Logging\Service\LogParserService::instance()->getRows($this->_getLogDirectory(), $this->_getLogFileName(), $offset, $limit, $this->_getLogFormat(), $query);
        if ($tz) {
            $entries = $this->_setTimezone($entries, $tz);
        }
        return $entries;
    }
    private function _setTimezone($entries, $tz)
    {
        foreach ($entries as $i => $entry) {
            $date = new \DateTime($entries[$i]["datetime"]);
            $date->setTimezone($tz);
            $entries[$i]["datetime"] = $date->format(DATETIME_FORMAT);
        }
        return $entries;
    }
    public function getMaxSize()
    {
        return \Traffic\Service\ConfigService::instance()->get("system", "log_max_size", 1000000);
    }
    public function checkSize()
    {
        $path = $this->_getLogDirectory();
        $list = \Core\Logging\Service\LogParserService::instance()->getLogList($path, $this->_getLogFileName());
        rsort($list);
        $sizeSum = 0;
        foreach ($list as $file) {
            $filename = $path . "/" . $file;
            $sizeSum += filesize($filename);
            if ($this->getMaxSize() < $sizeSum) {
                unlink($filename);
            }
        }
    }
}

?>