<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Service;

class DomainSSLLogsService extends \Traffic\Service\AbstractService
{
    private $_fileSystemService = NULL;
    private $_logsDir = NULL;
    const LOGS_DIR = "/opt/keitaro/log/";
    public function __construct()
    {
        $this->_fileSystemService = \Core\FileSystem\Service\FileSystemService::instance();
        $this->_logsDir = LOGS_DIR;
        if (\Core\Application\Application::instance()->isTesting()) {
            $this->_logsDir = TESTS_ROOT . "/fixtures/enable_ssl";
        } else {
            if (\Core\Application\Application::instance()->isDevelopment()) {
                $this->_logsDir = ROOT . "/var/log";
            }
        }
    }
    public function getLogFiles()
    {
        return $this->_fileSystemService->getGlobFiles($this->_logsDir . "/enable-ssl*", true);
    }
    public function getRows()
    {
        if (!DomainCheckerService::instance()->checkInstallReadyGetSSL()) {
            return [];
        }
        $rows = [];
        foreach ($this->getLogFiles() as $logFile) {
            $datetime = \DateTime::createFromFormat("U", $logFile->getCTime())->format(\Traffic\Logging\Service\BaseLoggerService::DATETIME_FORMAT);
            $rows[] = ["datetime" => $datetime, "message" => $logFile->getFilename() . ": " . $this->_getLogFileDomains($logFile)];
        }
        return $rows;
    }
    public function getFileContents($filename)
    {
        $filePath = $this->_logsDir . DIRECTORY_SEPARATOR . basename($filename);
        if (!$this->_fileSystemService->exists($filePath)) {
            return false;
        }
        return file_get_contents($filePath);
    }
    public function cleanLog()
    {
        foreach ($this->getLogFiles() as $logFile) {
            unlink($logFile->getPathname());
        }
    }
    private function _getLogFileDomains(\SplFileInfo $file)
    {
        $fileObject = $file->openFile();
        $matches = [];
        foreach ($fileObject as $line) {
            if (preg_match("/Command:.*-D\\s(.*)/", $line, $matches)) {
                $matches[1] ? exit : "";
            }
        }
    }
}

?>