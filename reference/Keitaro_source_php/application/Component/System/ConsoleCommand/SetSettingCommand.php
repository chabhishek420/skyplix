<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConsoleCommand;

class SetSettingCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Set license key")->setName("system:set_setting")->addArgument("name", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "Setting name")->addArgument("value", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "Setting value");
    }
    protected function execute(string $execute, \Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (\Traffic\Tools\Tools::isRootUser() && !\Core\Application\Application::instance()->isTesting()) {
            $message = "User 'root' can't run this command. \n                You must use 'sudo -u keitaro php bin/cli.php system:set_setting'";
            $output->writeln($message);
            return 1;
        }
        $name = $input->getArgument("name");
        $value = $input->getArgument("value");
        $setting = \Traffic\Settings\Repository\SettingsRepository::instance()->findByKey($name);
        if ($setting === NULL) {
            $output->writeln("<error>Setting \"" . $name . "\" not found.</error>");
            return 1;
        }
        \Traffic\Service\SettingsService::instance()->updateValue($name, $value);
        $output->writeln("<info>Setting \"" . $name . "\" changed from \"" . $setting->get("value") . "\" to \"" . $value . "\" </info>");
        return 0;
    }
}

?>