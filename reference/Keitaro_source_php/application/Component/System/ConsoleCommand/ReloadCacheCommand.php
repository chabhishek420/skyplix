<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConsoleCommand;

class ReloadCacheCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Reload cache (don't run the command as root!)")->setName("system:reload_cache");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (\Traffic\Tools\Tools::isRootUser()) {
            $message = "User 'root' can't run this command. \n                You must use 'sudo -u USER php bin/cli.php system:reload_cache'";
            $output->writeln($message);
            return 1;
        }
        \Traffic\Service\SettingsService::instance()->refreshCache();
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
        $output->writeln("Complete");
        return 0;
    }
}

?>