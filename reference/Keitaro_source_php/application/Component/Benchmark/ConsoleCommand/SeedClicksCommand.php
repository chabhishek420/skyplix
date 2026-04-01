<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark\ConsoleCommand;

class SeedClicksCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Seed clicks")->setName("seed:clicks")->addOption("count", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Count records", 100)->addOption("range", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_REQUIRED, "Time range (minutes)", 30)->addOption("random", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Use random count");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $output->setVerbosity(\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_DEBUG);
        $count = $input->getOption("count");
        $range = $input->getOption("range");
        $rand = $input->getOption("random");
        $seeder = new \Component\Benchmark\Seeder($output);
        if ($rand) {
            $count = rand(0, $count);
            $seeder->createClicks($count, $range);
        } else {
            $seeder->createClicks($count, $range);
        }
        $output->write("Done");
        return 0;
    }
}

?>