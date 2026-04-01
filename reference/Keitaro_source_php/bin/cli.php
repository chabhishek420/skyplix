<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

require_once __DIR__ . "/_init.php";
switch ($argv[1]) {
    case "benchmark":
        Core\Application\Application::instance()->setEnv(Core\Application\Application::TESTING);
        break;
    default:
        $application = new Symfony\Component\Console\Application();
        $application->setName("Keitaro Command Tools");
        foreach (Component\Console\Repository\ConsoleCommandRepository::instance()->getCommands() as $command) {
            $application->add($command);
        }
        $application->run();
}

?>