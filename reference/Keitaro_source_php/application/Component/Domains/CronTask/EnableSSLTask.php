<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\CronTask;

class EnableSSLTask implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::OTHER;
    const INTERVAL_MINUTES = 1;
    const DOMAINS_SLL_LIMIT = 20;
    public function run()
    {
        $domainsAwaiting = \Component\Domains\Repository\DomainsRepository::instance()->allActiveAndAwaitingSSL(DOMAINS_SLL_LIMIT);
        \Component\Domains\Service\DomainCommandService::instance()->enableSSLCommand($domainsAwaiting);
    }
    public function isReady(\DateTime $executedAt)
    {
        $comparableDate = new \DateTime("-" . INTERVAL_MINUTES . " minutes");
        return $executedAt < $comparableDate;
    }
}

?>