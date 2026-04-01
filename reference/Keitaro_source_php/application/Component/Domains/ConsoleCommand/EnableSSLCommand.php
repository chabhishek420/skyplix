<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\ConsoleCommand;

class EnableSSLCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Enables SSL for domains awaiting certificates")->setName("domains:enable_ssl")->addArgument("domains", \Symfony\Component\Console\Input\InputArgument::OPTIONAL, "Domains list separated by commas WITHOUT spaces and WITHOUT scheme. E.g domain1.com,domain2.com,domain3.com");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $domainsAwaiting = $this->_getProcessingDomains($input, $output);
        if (count($domainsAwaiting) === 0) {
            $output->writeln("No domains to process. Exiting.");
            return 1;
        }
        $enabledDomains = \Component\Domains\Service\DomainCommandService::instance()->enableSSLCommand($domainsAwaiting);
        $enabledDomainsNames = array_map(function (\Traffic\Model\Domain $domain) {
            return $domain->getName();
        }, $enabledDomains);
        $output->writeln("SSL certificates were successfully issued for domains: " . implode(", ", $enabledDomainsNames));
        return 0;
    }
    private function _getProcessingDomains($_getProcessingDomains, \Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $argDomains = trim($input->getArgument("domains"));
        $domainService = \Component\Domains\Service\DomainService::instance();
        if ($argDomains) {
            if (strpos($argDomains, " ") !== false) {
                $output->writeln("Should be no whitespaces in domain list");
                return [];
            }
            $argDomains = explode(",", $argDomains);
            $argDomains = array_map(function ($argDomain) {
                return $domainService->convertDomainName($argDomain);
            }, $argDomains);
            $domainsAwaiting = \Component\Domains\Repository\DomainsRepository::instance()->getAllByNames($argDomains);
        } else {
            $domainsAwaiting = \Component\Domains\Repository\DomainsRepository::instance()->allActiveAndAwaitingSSL();
        }
        return $domainsAwaiting;
    }
}

?>