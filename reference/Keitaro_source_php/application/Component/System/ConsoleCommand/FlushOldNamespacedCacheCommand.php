<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConsoleCommand;

class FlushOldNamespacedCacheCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setName("system:flush_old_cache")->setDescription("Clears old doctrine cache with old namespace versions");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        \Traffic\Cache\CacheService::instance()->flushOldNamespacedCache();
        $output->writeln("Old cache successfully flushed");
        return 0;
    }
}

?>