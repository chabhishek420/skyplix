<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\ConsoleCommand;

class CreateAdminCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Create an admin")->setName("admins:create")->setAliases(["admin:create"])->addArgument("login", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "User login")->addArgument("password", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "User password")->addOption("language", "", \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Preferred Language", "en")->addOption("timezone", "", \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Preferred Timezone", "UTC");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $login = $input->getArgument("login");
        $password = $input->getArgument("password");
        $timezone = $input->getOption("timezone");
        $language = $input->getOption("language");
        \Component\Users\Service\UserService::instance()->createUser(["type" => \Component\Users\Model\User::TYPE_ADMIN, "login" => $login, "new_password" => $password, "new_password_confirmation" => $password, "rules" => [], "preferences" => ["language" => $language, "timezone" => $timezone]]);
        $output->writeln("Admin '" . $login . "' created");
        return 0;
    }
}

?>