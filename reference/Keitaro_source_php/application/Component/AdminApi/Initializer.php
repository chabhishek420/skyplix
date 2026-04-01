<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AdminApi;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadConsoleCommands(\Component\Console\Repository\ConsoleCommandRepository $repo)
    {
        $repo->registerConsoleCommand(new ConsoleCommand\AdminApiRoutesCommand());
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("adminApi", new Controller\AdminApiController());
    }
}

?>