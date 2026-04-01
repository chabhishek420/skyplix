<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark\ConsoleCommand;

class BenchmarkCommandProcessorCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Run benchmark command processing")->setName("benchmark:commands")->addOption("count", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Count requests", 100)->addOption("rounds", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Number of rounds", "1");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $count = $input->getOption("count");
        $rounds = $input->getOption("rounds");
        $benchmark = new \Component\Benchmark\Benchmark($output);
        $benchmark->init();
        $benchmark->clearTable();
        $benchmark->dataProcessor($count, $rounds);
        return 0;
    }
}

?>