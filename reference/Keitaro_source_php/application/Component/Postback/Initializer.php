<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("postbackTemplates", new Controller\PostbackTemplatesController());
    }
    public function loadDelayedCommands(\Component\DelayedCommands\Repository\DelayedCommandRepository $repo)
    {
        $repo->register(DelayedCommand\ProcessPostbackCommand::NAME, new DelayedCommand\ProcessPostbackCommand());
    }
}

?>