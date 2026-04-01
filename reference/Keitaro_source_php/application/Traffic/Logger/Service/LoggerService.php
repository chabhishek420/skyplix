<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Logging\Service;

class LoggerService extends BaseLoggerService
{
    protected $_contextName = "unknown";
    protected $_stopWords = ["unlink", "rmdir", "chmod", "No such file or directory", "is not within the allowed path(s): (C:/Inetpub", "already loaded", "disk_free_space", "is_readable()"];
    protected $_handler = NULL;
    const WARNING = "warning";
    const DEBUG = "debug";
    const BUFFER_LIMIT = 10000;
    public function getLevel()
    {
        $level = \Traffic\Service\ConfigService::instance()->get("system", "log_level", WARNING);
        if ($level == "warn") {
            $level = WARNING;
        }
        return $level;
    }
    protected function _getLogLevel()
    {
        return constant("Monolog\\Logger::" . strtoupper($this->getLevel()));
    }
    protected function _getLogFormat()
    {
        return \Core\Logging\Service\LogParserService::SYSTEM_FORMAT;
    }
    protected function _getDefaultLogFileName()
    {
        return \Core\Application\Application::instance()->getEnv();
    }
    protected function _getLogFormatter()
    {
        return $this->_getSystemLogFormatter();
    }
    public function setContextName($name)
    {
        $this->_contextName = $name;
    }
    public function __construct($dir = NULL)
    {
        if (!empty($dir)) {
            $this->setLogDirectory($dir);
        }
        $this->_logger = new \Monolog\Logger("main");
        $formatter = $this->_getLogFormatter();
        $handler = new \Monolog\Handler\StreamHandler($this->_getLogPath(), $this->_getLogLevel());
        $handler->setFormatter($formatter);
        $this->_logger->setHandlers([$handler]);
    }
    public function enableBuffer()
    {
        $handler = new \Monolog\Handler\RotatingFileHandler($this->_getLogPath(), 4, $this->_getLogLevel());
        $handler = new \Monolog\Handler\BufferHandler($handler, BUFFER_LIMIT, $this->_getLogLevel(), true, true);
        $this->_logger->setHandlers([$handler]);
    }
    public function flush()
    {
        $handler = $this->_logger->popHandler();
        if ($handler instanceof \Monolog\Handler\BufferHandler) {
            $handler->flush();
        }
    }
    public function closeHandler()
    {
        $handler = $this->_logger->popHandler();
        $handler->close();
    }
    public function info($msg)
    {
        $this->log(\Monolog\Logger::INFO, $msg);
    }
    public function debug($msg)
    {
        $this->log(\Monolog\Logger::DEBUG, $msg);
    }
    public function warning($msg)
    {
        $this->log(\Monolog\Logger::WARNING, $msg);
    }
    public function error($msg)
    {
        $this->log(\Monolog\Logger::ERROR, $msg);
    }
    public function log($level, $msg)
    {
        if ($level == \Monolog\Logger::DEBUG && $this->getLevel() != DEBUG) {
            return NULL;
        }
        $this->_logger->log($level, $this->_unpackMessage($msg), [$this->_contextName]);
    }
    public function setErrorHandlers()
    {
        register_shutdown_function([$this, "shutdownHandler"]);
        set_error_handler([$this, "errorHandler"]);
    }
    public function shutdownHandler()
    {
        $lastError = error_get_last();
        if (!isset($lastError["type"]) || empty($lastError["type"])) {
            return NULL;
        }
        switch ($lastError["type"]) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_PARSE:
                $error = $this->formatPHPError($lastError["type"], $lastError["message"], $lastError["file"], $lastError["line"]);
                $this->error($error);
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $error = $this->formatPHPError($lastError["type"], $lastError["message"], $lastError["file"], $lastError["line"]);
                $this->log(\Monolog\Logger::NOTICE, $error);
                break;
        }
    }
    protected function getErrorName($code)
    {
        $error = "";
        switch ($code) {
            case E_PARSE:
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $error = "Fatal Error";
                break;
            case E_WARNING:
            case E_USER_WARNING:
            case E_COMPILE_WARNING:
            case E_RECOVERABLE_ERROR:
                $error = "Warning";
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $error = "Notice";
                break;
            case E_STRICT:
                $error = "Strict";
                break;
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $error = "Deprecated";
                break;
            default:
                return $error;
        }
    }
    protected function formatPHPError($error_level, $error_message, $error_file, $error_line)
    {
        return $this->getErrorName($error_level) . " in " . $error_file . " line " . $error_line . ": " . $error_message;
    }
    public function errorHandler($error_level, $error_message, $error_file, $error_line)
    {
        if ($this->_shouldFilter($error_message)) {
            return NULL;
        }
        $error = $this->formatPHPError($error_level, $error_message, $error_file, $error_line);
        switch ($error_level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $this->error($error);
                break;
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $this->log(\Monolog\Logger::WARNING, $error);
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $this->log(\Monolog\Logger::NOTICE, $error);
                break;
            case E_STRICT:
                $this->log(\Monolog\Logger::DEBUG, $error);
                break;
            default:
                $this->log(\Monolog\Logger::WARNING, $error);
        }
    }
    protected function _shouldFilter($message)
    {
        foreach ($this->_stopWords as $word) {
            if (strpos($message, $word) !== false) {
                return true;
            }
        }
        return false;
    }
    private function _unpackMessage($msg)
    {
        if (is_callable($msg)) {
            return $msg();
        }
        return $msg;
    }
}

?>