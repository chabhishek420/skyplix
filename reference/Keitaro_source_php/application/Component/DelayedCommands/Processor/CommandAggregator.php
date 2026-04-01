<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\DelayedCommands\Processor;

class CommandAggregator
{
    private $_heap = [];
    private $_limit = NULL;
    const DEFAULT_CHUNK_SIZE = 4000;
    const PAYLOAD = "payload";
    const COMMAND = "command";
    public function __construct()
    {
        $this->_limit = \Traffic\Service\ConfigService::instance()->get("system", "data_processor_chunk_size", DEFAULT_CHUNK_SIZE);
    }
    public function getLimit()
    {
        return $this->_limit;
    }
    public function setLimit($limit)
    {
        return $this->_limit = $limit;
    }
    public function get($commandName)
    {
        $command = \Component\DelayedCommands\Repository\DelayedCommandRepository::instance()->find($commandName);
        return $this->_heap[$command->priority()][$commandName];
    }
    public function store($data = NULL)
    {
        if (empty($data[COMMAND])) {
            $data = json_encode($data);
            throw new \Core\Application\Exception\Error("command is null: " . $data);
        }
        $commandName = $data[COMMAND];
        $command = \Component\DelayedCommands\Repository\DelayedCommandRepository::instance()->find($commandName);
        if (empty($this->_heap[$command->priority()])) {
            $this->_heap[$command->priority()] = [];
        }
        if (empty($this->_heap[$command->priority()][$commandName])) {
            $this->_heap[$command->priority()][$commandName] = [];
        }
        $this->_heap[$command->priority()][$commandName][] = $data[PAYLOAD];
        if ($this->getLimit() <= count($this->_heap[$command->priority()][$commandName])) {
            $this->flushAll();
        }
    }
    public function flush($commandName, $payloads)
    {
        \Traffic\Logging\Service\LoggerService::instance()->info("Process '" . $commandName . "' " . count($payloads) . " commands");
        try {
            $command = \Component\DelayedCommands\Repository\DelayedCommandRepository::instance()->find($commandName);
            $command->process($payloads);
        } catch (\Core\Exceptions\NotFoundError $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
        }
    }
    public function flushAll()
    {
        ksort($this->_heap);
        foreach ($this->_heap as $priority => $items) {
            foreach ($items as $commandName => $payloads) {
                if (!empty($payloads)) {
                    $this->flush($commandName, $payloads);
                }
                $this->_heap[$priority][$commandName] = [];
            }
        }
    }
}

?>