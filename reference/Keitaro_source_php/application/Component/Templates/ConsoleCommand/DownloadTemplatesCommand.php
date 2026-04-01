<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Templates\ConsoleCommand;

class DownloadTemplatesCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Download templates")->setName("templates:download");
    }
    protected function execute(string $execute, \Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $downloader = new \Component\Templates\TemplateDownloader\TemplateDownloader(\Component\Templates\Info\Info::TEMPLATES);
        $downloader->download();
        $output->writeln("<info>Templates downloaded!</info>");
        return 0;
    }
}

?>