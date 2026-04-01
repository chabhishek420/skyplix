<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\ConsoleCommand;

class ChangeAdminPasswordCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Change admin user password")->setName("admins:reset_password")->setAliases(["admin:change-password", "admin:reset_password", "admins:change-password"])->addArgument("password", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "Admin password")->addOption("login", "", \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, "Specify login", "admin")->addOption("drop-sessions", "", \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, "Drop current user sessions", true);
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $password = $input->getArgument("password");
        $login = $input->getOption("login");
        $dropSessions = $input->getOption("drop-sessions");
        $user = \Component\Users\Repository\UserRepository::instance()->findLast("login = " . \Core\Db\Db::quote($login));
        if (empty($user)) {
            return $output->writeln("User '" . $login . "' is not found");
        }
        \Component\Users\Service\UserService::instance()->changePassword($user, $password, $password, $dropSessions && $dropSessions != "false");
        $output->writeln("Password for " . $login . " successfully changed");
        return 0;
    }
}

?>