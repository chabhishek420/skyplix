<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConsoleCommand;

class SetLicenseKeyCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Set license key")->setName("system:set_license_key")->addArgument("key", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "License key (XXXX-XXXX-XXXX-XXXX)");
    }
    protected function execute(string $execute, \Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (\Traffic\Tools\Tools::isRootUser()) {
            $message = "User 'root' can't run this command. \n                You must use 'sudo -u keitaro php bin/cli.php system:set_license_key'";
            $output->writeln($message);
            return 1;
        }
        $key = $input->getArgument("key");
        if (!\Core\Application\LicenseService::instance()->isValidLicenseKey($key)) {
            $output->writeln("<error>License key is invalid</error>");
            return 1;
        }
        \Core\Application\LicenseService::instance()->saveLicenseKey($key);
        \Traffic\CachedData\WarmupScheduler::scheduleWarmup();
        $output->writeln("<info>License key is set!</info>");
        return 0;
    }
}

?>