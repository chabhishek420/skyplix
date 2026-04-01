<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Logging\Service;

class PostbackLoggerService extends \Traffic\Logging\Service\BaseLoggerService
{
    protected function _getLogLevel()
    {
        return \Monolog\Logger::INFO;
    }
    protected function _getDefaultLogFileName()
    {
        return "postbacks";
    }
    protected function _getLogFormat()
    {
        return LogParserService::POSTBACK_FORMAT;
    }
    protected function _getLogFormatter()
    {
        return $this->_getSimpleLogFormatter();
    }
    public function __construct()
    {
        $formatter = $this->_getLogFormatter();
        $this->_logger = new \Monolog\Logger("postbacks");
        $stream = new \Monolog\Handler\RotatingFileHandler($this->_getLogPath(), 2, $this->_getLogLevel());
        $stream->setFilenameFormat("{filename}-{date}", \Monolog\Handler\RotatingFileHandler::FILE_PER_MONTH);
        $stream->setFormatter($formatter);
        $this->_logger->pushHandler($stream);
    }
    public function log($msg)
    {
        if (!\Traffic\Service\ConfigService::instance()->get("system", "postback_log")) {
            return NULL;
        }
        $this->_logger->info($msg, [$this->_contextName]);
    }
}

?>