<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark\ConsoleCommand;

class BenchmarkTrafficCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Run benchmark traffic")->setName("benchmark:traffic")->addOption("count", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Count requests", 100)->addOption("storage", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, "Storage type");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $output->setVerbosity(\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG);
        $count = $input->getOption("count");
        $storage = $input->getOption("storage");
        $benchmark = new \Component\Benchmark\Benchmark($output);
        $benchmark->init();
        $benchmark->clearTable();
        $benchmark->traffic($count, $storage);
        $output->write("Done");
        return 0;
    }
}

?>