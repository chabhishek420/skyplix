<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Settings\ConsoleCommand;

class SettingsCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Reset allow domain for admin panel")->setName("admins:reset_allowed_domains")->setAliases(["admin:reset_allowed_domains"]);
    }
    protected function execute(string $execute, \Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        \Traffic\Service\SettingsService::instance()->updateValue(\Traffic\Model\Setting::ADMIN_ALLOWED_DOMAINS, NULL);
        $output->writeln("<info>Setting updated</info>");
        return 0;
    }
}

?>