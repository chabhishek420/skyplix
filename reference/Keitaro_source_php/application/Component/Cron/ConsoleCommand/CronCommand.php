<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cron\ConsoleCommand;

class CronCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Run cron task (don't run the command as root!)")->setName("cron:run");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        if (\Traffic\Tools\Tools::isRootUser()) {
            $output->writeln("User 'root' can't run this command. You must use 'sudo -u USER php bin/cli.php cron:run'");
            return 1;
        }
        \Core\Application\Bootstrap::initLogger();
        $runner = new \Cron\CronTaskRunner\CronTaskRunner();
        $runner->runTasks(NULL);
        return 0;
    }
}

?>