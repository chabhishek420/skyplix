<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Logging\Service;

class SentPostbackLoggerService extends \Traffic\Logging\Service\BaseLoggerService
{
    protected function _getLogLevel()
    {
        return \Monolog\Logger::INFO;
    }
    protected function _getDefaultLogFileName()
    {
        return "sent_postbacks";
    }
    protected function _getLogFormat()
    {
        return LogParserService::SENT_POSTBACK_FORMAT;
    }
    protected function _getLogFormatter()
    {
        return $this->_getSimpleLogFormatter();
    }
    public function __construct()
    {
        $formatter = $this->_getLogFormatter();
        $this->_logger = new \Monolog\Logger("send_postbacks");
        $stream = new \Monolog\Handler\RotatingFileHandler($this->_getLogPath(), 2, $this->_getLogLevel());
        $stream->setFilenameFormat("{filename}-{date}", \Monolog\Handler\RotatingFileHandler::FILE_PER_MONTH);
        $stream->setFormatter($formatter);
        $this->_logger->pushHandler($stream);
    }
    public function log($msg)
    {
        $this->_logger->info($msg, [$this->_contextName]);
    }
}

?>