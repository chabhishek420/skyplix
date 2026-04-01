<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Service;

class DomainCheckerService extends \Traffic\Service\AbstractService
{
    private $_domainService = NULL;
    private $_httpService = NULL;
    const PING_DOMAIN_PATH = "?_ping=domain";
    const LIMIT_SSL_ATTEMPTS = 25;
    const NEXT_CHECK_RATE = 1.5;
    public function __construct()
    {
        $this->_domainService = DomainService::instance();
        $this->_httpService = \Traffic\Http\Service\HttpService::instance();
    }
    public function updateDomainsStatus($domains, $basePath)
    {
        $checkedDomainsHttp = $this->checkDomainsStatus($domains, $basePath);
        $checkedDomainsHttps = $this->checkDomainsStatus($checkedDomainsHttp, $basePath, true);
        foreach ($checkedDomainsHttps as $checkedDomain) {
            $checkedDomain = $this->setNextCheck($checkedDomain);
            $this->_domainService->save($checkedDomain);
        }
        return $domains;
    }
    public function checkDomainsStatus($domains, $basePath, $isCheckWithSSL = false)
    {
        $domainsById = $this->_getDomainsById($domains);
        $domainsPromises = $this->_prepareDomainsPromises($domainsById, $basePath, $isCheckWithSSL);
        $responses = \Traffic\Http\Service\HttpService::settleLimit($domainsPromises, 15)->wait();
        return $this->_checkDomains($responses, $domainsById, $isCheckWithSSL);
    }
    public function setAwaitingSSL($domains)
    {
        foreach ($domains as $domain) {
            $status = $this->isSSLEnablingAvailable($domain) ? \Traffic\Model\Domain::SSL_STATUS_AWAITING_SSL : \Traffic\Model\Domain::SSL_STATUS_AWAITING_DNS;
            $domain->set("ssl_status", $status);
            $this->_domainService->save($domain);
        }
        return $domains;
    }
    public function setSSLEnabled($domains)
    {
        foreach ($domains as $domain) {
            $this->_domainService->update($domain, ["is_ssl" => 1, "ssl_status" => \Traffic\Model\Domain::SSL_STATUS_ISSUED, "network_status" => \Traffic\Model\Domain::NETWORK_STATUS_ACTIVE, "error_description" => ""]);
        }
        return $domains;
    }
    public function setErrorSSLEnabled($domains)
    {
        foreach ($domains as $domain) {
            $this->_domainService->update($domain, ["is_ssl" => 0, "ssl_status" => \Traffic\Model\Domain::SSL_STATUS_ERROR, "error_description" => ""]);
        }
        return $domains;
    }
    public function isSSLEnablingAvailable(\Traffic\Model\Domain $domain)
    {
        return !$domain->isSSL() && $domain->isAssociated();
    }
    public function checkInstallReadyGetSSL()
    {
        if (!\Component\System\Service\StatusService::instance()->isApprovedInstallation()) {
            return false;
        }
        if (\Component\System\Service\StatusService::instance()->getCurrentVersion() < 0) {
            return false;
        }
        return true;
    }
    public function prepareMassCheck($domains)
    {
        $nextCheckAt = new \DateTime("-1 minutes");
        foreach ($domains as $domain) {
            $domain = $this->setNextCheck($domain, $nextCheckAt);
            $domain->set("network_status", \Traffic\Model\Domain::NETWORK_STATUS_VALIDATING);
            $this->_domainService->save($domain);
        }
    }
    public function setNextCheck(\Traffic\Model\Domain $setNextCheck, \Traffic\Model\Domain $domain = NULL, \DateTime $nextCheckAt)
    {
        $checkRetries = $domain->get("check_retries");
        if (is_null($nextCheckAt)) {
            $nextCheckAt = new \DateTime("+" . ceil(NEXT_CHECK_RATE ** $checkRetries) . " minutes");
        }
        $domain->set("next_check_at", $nextCheckAt);
        $newCheckRetries = 25 <= $checkRetries ? 25 : ++$checkRetries;
        $domain->set("check_retries", $newCheckRetries);
        return $domain;
    }
    private function _checkDomains($httpResponses, $domainsById, $isCheckWithSSL)
    {
        foreach ($httpResponses as $domainId => $result) {
            $domainCheckResult = $this->_checkIsDomainActive($result);
            $currentDomain = $domainsById[$domainId];
            $isSSLDomain = $currentDomain->isSSL();
            $updatedFields = [];
            if ($domainCheckResult->isActive()) {
                $updatedFields = ["network_status" => \Traffic\Model\Domain::NETWORK_STATUS_ACTIVE, "error_description" => ""];
                $isSSLStatusErrorOrAwaiting = in_array($currentDomain->get("ssl_status"), [\Traffic\Model\Domain::SSL_STATUS_ERROR, \Traffic\Model\Domain::SSL_STATUS_AWAITING_SSL]);
                if ($isSSLDomain && $isSSLStatusErrorOrAwaiting) {
                    $updatedFields["ssl_status"] = NULL;
                }
                if ($isCheckWithSSL) {
                    $updatedFields["is_ssl"] = 1;
                    if (!in_array($currentDomain->getSSLStatus(), [\Traffic\Model\Domain::SSL_STATUS_ISSUED, \Traffic\Model\Domain::SSL_STATUS_AWAITING_SSL])) {
                        $updatedFields["ssl_status"] = $this->_checkIsDomainReadyToGetSSL($currentDomain, true);
                    }
                }
            } else {
                if (!$isCheckWithSSL) {
                    $updatedFields = ["network_status" => \Traffic\Model\Domain::NETWORK_STATUS_ERROR, "error_description" => $domainCheckResult->getError()];
                } else {
                    if ($currentDomain->isActive()) {
                        $updatedFields["ssl_status"] = $this->_checkIsDomainReadyToGetSSL($currentDomain);
                    } else {
                        $updatedFields = ["ssl_status" => \Traffic\Model\Domain::SSL_STATUS_ERROR];
                    }
                }
            }
            array_walk($updatedFields, function ($value, $key) {
                $currentDomain->set($key, $value);
            });
        }
        return $domainsById;
    }
    private function _checkIsDomainReadyToGetSSL(\Traffic\Model\Domain $domain, $allowBySSL = false)
    {
        if (!$this->checkInstallReadyGetSSL()) {
            return "";
        }
        $data = $domain->getSSLData();
        if (LIMIT_SSL_ATTEMPTS <= $data->totalSSLAttempts()) {
            if ($allowBySSL) {
                return \Traffic\Model\Domain::SSL_STATUS_AWAITING_SSL;
            }
            return \Traffic\Model\Domain::SSL_STATUS_BLOCK_SSL;
        }
        $checks = [];
        if ($data->checks()) {
            $checks = array_filter($data->checks(), function ($time) {
                return time() - $time < 3600;
            });
            $data->setCheck(array_values($checks));
            DomainService::instance()->update($domain, ["ssl_data" => $data->toArray()]);
        }
        if (count($checks) < 5) {
            return \Traffic\Model\Domain::SSL_STATUS_AWAITING_SSL;
        }
        return \Traffic\Model\Domain::SSL_STATUS_LIMIT_SSL;
    }
    private function _checkIsDomainActive($result)
    {
        $resultState = $result["state"];
        if ($resultState === \GuzzleHttp\Promise\PromiseInterface::FULFILLED) {
            $response = $result["value"];
            $domainResponseError = $this->_checkDomainResponse((int) $response->getBody());
            if (is_null($domainResponseError)) {
                return new \Component\Domains\DTO\DomainCheckResultValueObject(NULL);
            }
            return new \Component\Domains\DTO\DomainCheckResultValueObject($domainResponseError);
        }
        if ($resultState === \GuzzleHttp\Promise\PromiseInterface::REJECTED) {
            return new \Component\Domains\DTO\DomainCheckResultValueObject($result["reason"]);
        }
        return new \Component\Domains\DTO\DomainCheckResultValueObject("Unknown error");
    }
    private function _checkDomainResponse($result)
    {
        $errorMessage = NULL;
        if (empty($result)) {
            $errorMessage = "Domain returned empty response";
        } else {
            if (strstr($result, $this->_domainService->getTrackerCode()) === false) {
                $errorMessage = "Domain returned unexpected response";
            }
        }
        return $errorMessage;
    }
    private function _prepareDomainsPromises($domains, $basePath, $withHttps = false)
    {
        $options = [\GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => false, \GuzzleHttp\RequestOptions::VERIFY => true, \GuzzleHttp\RequestOptions::TIMEOUT => 7, \GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => 7];
        $domainsPromises = [];
        foreach ($domains as $domainId => $domain) {
            $preparedUrl = $this->_domainService->urlWithBasePath($domain, $basePath, $withHttps) . PING_DOMAIN_PATH;
            $domainsPromises[$domainId] = $this->_httpService->getAsync($preparedUrl, [], $options);
        }
        return $domainsPromises;
    }
    private function _getDomainsById($domains)
    {
        $domainsById = [];
        foreach ($domains as $domain) {
            if ($domain->getId()) {
                $domainsById[$domain->getId()] = $domain;
            }
        }
        return $domainsById;
    }
}

?>