<?php

namespace Cron\CronTaskRunner;

class CronTaskRunner
{
    const LOCK_NAME = "cron.lock";
    public function runTasks($channel)
    {
        if (!\Core\Application\LicenseService::instance()->getKey() && !\Core\Application\Application::instance()->isTesting()) {
            $msg = "The license key is not installed.";
            if (php_sapi_name() === "cli") {
                echo $msg . "\n";
            }
        } else {
            if (\Core\Application\Application::instance()->isSlaveModeEnabled()) {
                throw new \Exception("Slave mode enabled");
            }
            if (!$this->_securityCheck()) {
                throw new \Exception("Please open the control panel for more information");
            }
            if (\Core\Lock\LockService::instance()->isLocked(\Component\SelfUpdate\KeitaroUpdater\KeitaroUpdater::LOCK_NAME)) {
                $msg = "Keitaro is being updated. Skipping running cron tasks.";
                \Traffic\Logging\Service\LoggerService::instance()->info($msg);
                if (php_sapi_name() === "cli") {
                    echo $msg . "\n";
                }
            } else {
                $lock = \Core\Lock\LockService::instance()->tryLock($this->_getLockFile($channel));
                if (!$lock) {
                    $msg = "Another cron process is already running. Skipping running cron tasks.";
                    \Traffic\Logging\Service\LoggerService::instance()->info($msg);
                    if (php_sapi_name() === "cli") {
                        echo $msg . "\n";
                    }
                } else {
                    foreach (\Component\Cron\Repository\CronTaskRepository::instance()->getCronTasks() as $cronTask) {
                        $name = \Traffic\Tools\Tools::demodulize(get_class($cronTask));
                        $taskStatus = \Component\Cron\Service\CronService::instance()->getCurrentTaskStatus($name);
                        \Traffic\Logging\Service\LoggerService::instance()->info("CronService: checking task " . $name . " (channel " . $channel . ")");
                        if ($cronTask->isReady($taskStatus->getExecutedAt()) && $this->isAppropriateChannel($cronTask, $channel)) {
                            $cronTask->run();
                            \Component\Cron\Service\CronTaskStatusService::instance()->updateStatus($taskStatus);
                        } else {
                            \Traffic\Logging\Service\LoggerService::instance()->info("CronService: task " . $name . " is not ready or the channel is inappropriate");
                        }
                    }
                    \Core\Lock\LockService::instance()->unlock($lock, $this->_getLockFile($channel));
                }
            }
        }
    }
    public function isAppropriateChannel(\Component\Cron\CronTaskInterface $cronTask, $channel)
    {
        if (is_null($channel)) {
            return true;
        }
        if (isset($cronTask->channel) && $channel == $cronTask->channel) {
            return true;
        }
        return false;
    }
    private function _securityCheck()
    {
		return true;
        return file_exists(\Core\Application\TsService::instance()->getTimestampFile());
    }
    private function _getLockFile($channel)
    {
        if (is_null($channel)) {
            return LOCK_NAME;
        }
        return "cron-" . $channel . ".lock";
    }
}

?>