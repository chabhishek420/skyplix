<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConsoleCommand;

class GenerateConfigCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Generate new config.ini.php")->setName("system:generate_config")->addOption("config-sample-path", "", \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Save config to file", "application/config/config.ini.sample")->addOption("config-path", "", \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Save config to file", "application/config/config.ini.php")->addOption("env-file-path", "", \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Path to .env file")->addOption("force", "", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Force replace config")->addOption("prefix", "", \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Custom env prefix (ex 'TEST_')");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $configSamplePath = $input->getOption("config-sample-path");
        $configPath = $input->getOption("config-path");
        $force = $input->getOption("force");
        $envFilePath = $input->getOption("env-file-path");
        $prefix = $input->getOption("prefix");
        if (file_exists($configPath) && !$force) {
            $output->writeln("<error>" . $configPath . " exists, use --force to replace it</error>");
            return 1;
        }
        if (!file_exists($configSamplePath)) {
            $output->writeln("<error>" . $configSamplePath . " doesn't exists</error>");
            return 1;
        }
        $output->writeln("Creating config " . $configPath);
        $configGenerator = new \Component\System\ConfigGenerator\ConfigGenerator($configSamplePath, $envFilePath, $prefix);
        $configGenerator->saveTo($configPath);
        $output->writeln("Config saved to " . $configPath);
        return 0;
    }
}

?>