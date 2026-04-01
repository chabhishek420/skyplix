<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CommandQueue\QueueStorage;

class FileStorage implements StorageInterface
{
    private $_storagePath = NULL;
    const COMMAND_QUEUE = "command_queue.dat";
    const PROCESSING_FILE = "event_queue.processing.dat";
    const MAX_SAVE_ATTEMPTS = self::SLEEP_TIME_FOR_NEXT_ATTEMPT;
    const SLEEP_TIME_FOR_NEXT_ATTEMPT = 500;
    public function enableCompression()
    {
    }
    public function __construct()
    {
        $this->setStoragePath(ROOT . "/var/stores");
    }
    public function setStoragePath($path)
    {
        if (!is_dir($path)) {
            mkdir($path);
        }
        $this->_storagePath = $path;
    }
    public function isAvailable()
    {
        return is_writable($this->_storagePath);
    }
    public function push($command)
    {
        $this->_saveToFile($command);
        return $this;
    }
    private function _saveToFile($data, $cnt = 0)
    {
        if (MAX_SAVE_ATTEMPTS < $cnt) {
            \Traffic\Logging\Service\LoggerService::instance()->error("[FileStorage] Cant't save to " . $this->_getStorageFilePath() . ". File locked.");
        } else {
            $file = $this->_getStorageFile("a+");
            if (!flock($file, LOCK_EX | LOCK_NB)) {
                usleep(SLEEP_TIME_FOR_NEXT_ATTEMPT);
                return $this->_saveToFile($data, ++$cnt);
            }
            fwrite($file, $data . "\n");
            unset($data);
            flock($file, LOCK_UN);
            fclose($file);
        }
    }
    private function _beginDequeue()
    {
        if (!$this->_isAborted() && file_exists($this->_getStorageFilePath())) {
            rename($this->_getStorageFilePath(), $this->_getProcessingFilePath());
        }
        if (file_exists($this->_getProcessingFilePath())) {
            return new \SplFileObject($this->_getProcessingFilePath());
        }
    }
    private function _endDequeue()
    {
        if (file_exists($this->_getProcessingFilePath())) {
            unlink($this->_getProcessingFilePath());
        }
    }
    public function pop()
    {
        $file = $this->_beginDequeue();
        while ($file && !$file->eof()) {
            $string = $file->fgets();
            if (!$string) {
            }
        }
        unset($file);
        $this->_endDequeue();
    }
    public function count()
    {
        $count = 0;
        $files = [$this->_getStorageFilePath(), $this->_getProcessingFilePath()];
        foreach ($files as $filePath) {
            if (file_exists($filePath)) {
                $file = fopen($filePath, "r");
                while (!feof($file)) {
                    if (fgets($file) != "") {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }
    public function clean()
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[FileStorage] clean");
        if (file_exists($this->_getStorageFilePath())) {
            unlink($this->_getStorageFilePath());
        }
        if (file_exists($this->_getProcessingFilePath())) {
            unlink($this->_getProcessingFilePath());
        }
    }
    private function _isAborted()
    {
        return file_exists($this->_getProcessingFilePath());
    }
    private function _getStoragePath()
    {
        return $this->_storagePath;
    }
    private function _getStorageFilePath()
    {
        return $this->_getStoragePath() . "/" . COMMAND_QUEUE;
    }
    private function _getStorageFile($access)
    {
        return fopen($this->_getStorageFilePath(), $access);
    }
    private function _getProcessingFilePath()
    {
        return $this->_getStoragePath() . "/" . PROCESSING_FILE;
    }
}

?>