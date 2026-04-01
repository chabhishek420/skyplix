<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Service;

class DomainCommandService extends \Traffic\Service\AbstractService
{
    private $_lock = NULL;
    const LOCK_NAME = "enable_ssl";
    const PROCESS_TIMEOUT = 480;
    const ENABLE_SSL_COMMAND = "sudo /opt/keitaro/bin/kctl-enable-ssl -L en -D";
    public function enableSSLCommand($enableSSLCommand, $domains = NULL, \Symfony\Component\Process\Process $process)
    {
        $this->_waitForLock();
        if (count($domains) === 0) {
            $this->_unlock();
            return [];
        }
        $this->_updateSSLData($domains);
        if (!$process) {
            $process = $this->_createProcess($domains);
        }
        $process->run();
        if (!$process->isSuccessful()) {
            \Traffic\Logging\Service\LoggerService::instance()->error($process->getErrorOutput());
        }
        $output = $process->getOutput() . PHP_EOL . "Errors:" . PHP_EOL . $process->getErrorOutput();
        $output = $this->_filterOutputFromANSI($output);
        $successfullyEnabledDomainNames = $this->_processEnableSSLCommandOutput($output);
        $successfullyEnabledDomains = [];
        $failEnabledDomains = [];
        foreach ($domains as $domain) {
            if (in_array($domain->getName(), $successfullyEnabledDomainNames)) {
                $successfullyEnabledDomains[] = $domain;
            } else {
                $failEnabledDomains[] = $domain;
            }
        }
        DomainCheckerService::instance()->setSSLEnabled($successfullyEnabledDomains);
        DomainCheckerService::instance()->setErrorSSLEnabled($failEnabledDomains);
        $this->_unlock();
        return $successfullyEnabledDomains;
    }
    private function _updateSSLData($_updateSSLData, $domains)
    {
        foreach ($domains as $domain) {
            $sslData = $domain->getSSLData();
            $sslData->updateCheck();
            $sslData->updateTotal();
            DomainService::instance()->update($domain, ["ssl_data" => $sslData->toArray()]);
        }
    }
    private function _createProcess(\Symfony\Component\Process\Process $_createProcess, $domains)
    {
        $domainsNames = array_map(function (\Traffic\Model\Domain $domain) {
            return $domain->getName();
        }, $domains);
        $command = explode(" ", ENABLE_SSL_COMMAND);
        $command[] = implode(",", $domainsNames);
        return new \Symfony\Component\Process\Process($command, NULL, NULL, NULL, PROCESS_TIMEOUT);
    }
    private function _processEnableSSLCommandOutput($_processEnableSSLCommandOutput, $output)
    {
        $successStrings = ["SSL certificates are issued for domains:"];
        $isSuccess = false;
        foreach ($successStrings as $successString) {
            $successStringPosition = strpos($output, $successString);
            if ($successStringPosition !== false) {
                $isSuccess = true;
                if (!$isSuccess) {
                    return [];
                }
                $startOfString = $successStringPosition + strlen($successString);
                $endOfString = strpos($output, PHP_EOL, $successStringPosition);
                $successfulDomains = trim(substr($output, $startOfString, $endOfString - $startOfString));
                $successfulDomains = explode(", ", $successfulDomains);
                return $successfulDomains;
            }
        }
    }
    private function _waitForLock($_waitForLock)
    {
        $this->_lock = \Core\Lock\LockService::instance()->waitForLock(LOCK_NAME);
    }
    private function _unlock($_unlock)
    {
        \Core\Lock\LockService::instance()->unlock($this->_lock, LOCK_NAME);
    }
    private function _filterOutputFromANSI($_filterOutputFromANSI, $output)
    {
        return preg_replace("/\\e[[][A-Za-z0-9];?[0-9]*m?/", "", $output);
    }
}

?>