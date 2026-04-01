<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CommandQueue\Service;

class DelayedCommandService extends \Traffic\Service\AbstractService
{
    private $_storage = NULL;
    private $_compression = false;
    const PAYLOAD = "payload";
    const COMMAND = "command";
    const MAX_RETRIES = 3;
    const RETRY_PARAM = "retry";
    const COMMAND_NAME_PARAM = "command";
    public function setStorage(\Traffic\CommandQueue\QueueStorage\StorageInterface $storage)
    {
        $this->_storage = $storage;
    }
    private function getStorage()
    {
        return $this->_storage;
    }
    public function queueSize()
    {
        return $this->getStorage()->count();
    }
    public function clean()
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("[DelayedCommandService] clean");
        return $this->getStorage()->clean();
    }
    public function enableCompression()
    {
        return $this->_compression = true;
    }
    public function push($command)
    {
        if (empty($this->_storage)) {
            throw new \Exception("Storage is not set");
        }
        \Traffic\Logging\Service\LoggerService::instance()->debug(function () {
            $count = $this->queueSize();
            return "Pushed command: " . json_encode($command) . " current count " . $count;
        });
        $command = json_encode(\Traffic\Tools\Tools::utf8ize($command), JSON_PARTIAL_OUTPUT_ON_ERROR);
        $status = $this->getStorage()->push($command);
        \Traffic\Logging\Service\LoggerService::instance()->debug(function () {
            $count = $this->queueSize();
            return "Pushed. Count " . $count;
        });
        return $status;
    }
    public function popAll()
    {
        $result = [];
        foreach ($this->pop() as $item) {
            $result[] = $item;
        }
        return $result;
    }
    public function pop()
    {
        foreach ($this->getStorage()->pop() as $encodedData) {
            if (!empty($encodedData)) {
                $data = json_decode($encodedData, true);
                if (json_last_error()) {
                    \Traffic\Logging\Service\LoggerService::instance()->warning("An issue while decoding string \"" . $encodedData . "\" (" . json_last_error_msg() . "). Skipped.");
                }
            }
        }
    }
    public function count()
    {
        return $this->getStorage()->count();
    }
    public function isRetryAvailable($payload)
    {
        if (empty($payload[COMMAND_NAME_PARAM])) {
            return false;
        }
        $payload[RETRY_PARAM]++;
        if (MAX_RETRIES <= $payload[RETRY_PARAM]) {
            return false;
        }
        return true;
    }
    public function retry($payload)
    {
        if (!$this->isRetryAvailable($payload)) {
            return false;
        }
        $command = [PAYLOAD => $payload, COMMAND => $payload[COMMAND_NAME_PARAM]];
        $this->push($command);
        return true;
    }
    public function initRetry($payload, $commandName)
    {
        $payload[COMMAND_NAME_PARAM] = $commandName;
        $payload[RETRY_PARAM] = 0;
    }
}

?>