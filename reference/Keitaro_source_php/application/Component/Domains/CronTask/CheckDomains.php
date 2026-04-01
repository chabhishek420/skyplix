<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\CronTask;

class CheckDomains implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::OTHER;
    const INTERVAL_MINUTES = 1;
    const LIMIT_PER_CHECK = 20;
    public function run($run)
    {
        $this->_checkBasicLicense();
        $domains = $this->_findDomainsReadyToCheck();
        if (!empty($domains)) {
            \Component\Domains\Service\DomainCheckerService::instance()->updateDomainsStatus($domains, \Component\Domains\Service\DomainService::instance()->findCachedBasePath());
        }
    }
    public function isReady($isReady, \DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " minutes");
        return $executedAt < $comparableDate;
    }
    private function _checkBasicLicense($_checkBasicLicense)
    {
        if (\Core\Application\FeatureService::instance()->isBasic() && 1 < \Component\Domains\Repository\DomainsRepository::instance()->activeCount()) {
            \Component\Domains\Service\DomainService::instance()->disableOverspendingDomains();
        }
    }
    private function _findDomainsReadyToCheck($_findDomainsReadyToCheck)
    {
        $availableCheckTime = (new \DateTime())->format(\Core\Model\AbstractModel::DATETIME_FORMAT);
        return \Component\Domains\Repository\DomainsRepository::instance()->allActive("(network_status != " . \Core\Db\Db::quote(\Traffic\Model\Domain::NETWORK_STATUS_ACTIVE) . " OR network_status IS NULL OR ssl_status = " . \Core\Db\Db::quote(\Traffic\Model\Domain::SSL_STATUS_ERROR) . ")" . " AND (next_check_at < " . \Core\Db\Db::quote($availableCheckTime) . " OR next_check_at IS NULL)", "next_check_at", LIMIT_PER_CHECK);
    }
}

?>