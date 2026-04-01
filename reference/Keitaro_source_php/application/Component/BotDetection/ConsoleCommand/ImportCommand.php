<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\ConsoleCommand;

class ImportCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Import bot list (don't run the command as root!)")->setName("bots:import")->addArgument("filename", \Symfony\Component\Console\Input\InputArgument::REQUIRED, "Filename with list of bots. Fullpath or relative to current path");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (\Traffic\Tools\Tools::isRootUser()) {
            $output->writeln("User 'root' can't run this command. You must use 'sudo -u USER php bin/cli.php bots:import'");
            return 1;
        }
        $filename = $input->getArgument("filename");
        if (!is_file($filename)) {
            echo "Failed to find " . $filename;
            return 1;
        }
        $path = realpath($filename);
        echo "Parsing " . $path . "...\n";
        $list = file_get_contents($path);
        \Component\BotDetection\Service\UserBotsService::instance()->saveList($list);
        echo "Done\n";
        return 0;
    }
}

?>