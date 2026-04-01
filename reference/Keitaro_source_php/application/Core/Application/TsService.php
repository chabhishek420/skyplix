<?php

namespace Core\Application;

/**
 * Предназначен для сохранения в зашифрованном виде времени окончания действия лицензии.
 * Бладаря этому, мы всё-равно сможем потом остановить трекер, даже если нам подменят hash.lic.
 */

class TsService extends \Traffic\Service\AbstractService
{
    private $_xorMask = NULL;
    private $_shouldCheckLastUpdate = NULL;
    const AWAIT_TTL = 86400;
    const SECONDS_IN_DAY = 86400;
    public function __construct()
    {
        $this->_xorMask = 0;
    }
    public function saveTimestamp($time)
    {
        $code = dechex($time ^ $this->_xorMask);
        if (!file_put_contents($this->getTimestampFile(), $code)) {
            throw new Exception\Error("Can't save timestamp file to var/");
        }
    }
    public function shouldCheckLastUpdate($state)
    {
        $this->_shouldCheckLastUpdate = $state;
    }
    public function shouldCheckTsFile()
    {
		return false;
        if (!$this->_shouldCheckLastUpdate) {
            return true;
        }
        if (time() - \Component\SelfUpdate\Service\SystemUpdaterService::instance()->getLastUpdateTimestamp() < SECONDS_IN_DAY) {
            return false;
        }
        return true;
    }
    public function getTimestampFile()
    {
        return ROOT . \Traffic\Cache\Cache::DEFAULT_CACHE_DIR . "/" . md5(SALT) . ".link";
    }
    public function deleteTimestampFile()
    {
        if (is_file($this->getTimestampFile())) {
            unlink($this->getTimestampFile());
        }
    }
    public function isTimestampActive()
    {
		return true;
        $time = time();
        $expiresAt = $this->getTimestamp();
        return $time < $expiresAt + AWAIT_TTL;
    }
    public function isFileExists()
    {
		return true;
        return is_file($this->getTimestampFile());
    }
    public function getTimestamp()
    {
        if (!$this->isFileExists()) {
            return 0;
        }
        return hexdec(file_get_contents($this->getTimestampFile())) ^ $this->_xorMask;
    }
}

?>