<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Lock;

class LockService extends \Traffic\Service\AbstractService
{
    private function getFileName($lockName)
    {
        return $this->_getDirectory() . $lockName . ".lock";
    }
    public function unlock($lock, $lockName)
    {
        if (!empty($lock)) {
            fclose($lock);
        }
        $filename = $this->getFileName($lockName);
        if (file_exists($filename)) {
            unlink($filename);
        }
    }
    public function isLocked($lockName)
    {
        return file_exists($this->getFileName($lockName));
    }
    public function tryLock($lockName)
    {
        $this->_checkDirectory();
        $filename = $this->getFileName($lockName);
        $lock = fopen($filename, "w");
        if (!$lock) {
            return false;
        }
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return false;
        }
        return $lock;
    }
    public function waitForLock($lockName)
    {
        $this->_checkDirectory();
        $filename = $this->getFileName($lockName);
        $lock = fopen($filename, "w");
        if (!$lock) {
            return false;
        }
        if (!flock($lock, LOCK_EX)) {
            fclose($lock);
            return false;
        }
        return $lock;
    }
    private function _checkDirectory()
    {
        if (!is_dir($this->_getDirectory())) {
            mkdir($this->_getDirectory(), 511);
        }
    }
    private function _getDirectory()
    {
        return ROOT . "/var/locks/";
    }
}

?>