<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AdminApi\ConsoleCommand;

class AdminApiRoutesCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Show API routes")->setName("admin_api:routes");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        \Admin\AdminApi\AdminApiRoutesRepository::instance()->load();
        $prefix = "/admin_api/v1";
        $rows = [];
        foreach (\Admin\AdminApi\AdminApiRoutesRepository::instance()->getRoutes() as $route) {
            $url = preg_replace("#\\[[i|a]\\:(.*?)\\]#si", "{\$1}", $route["route"]);
            $rows[] = [$route["method"], $prefix . $url, $route["desc"]];
        }
        $table = new \Symfony\Component\Console\Helper\Table($output);
        $table->setHeaders(["Method", "Route", "Description"])->setRows($rows);
        $table->render();
        return 0;
    }
}

?>