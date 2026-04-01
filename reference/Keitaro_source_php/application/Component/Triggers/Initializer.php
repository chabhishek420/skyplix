<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers;

class Initializer extends \Core\Component\BaseInitializer
{
    public function loadCronTasks(\Component\Cron\Repository\CronTaskRepository $repo)
    {
        if (\Core\Application\FeatureService::instance()->hasExtensionsFeature()) {
            $repo->register(new CronTask\RunTriggersTask());
        }
        $repo->register(new CronTask\DeleteOldTriggers());
    }
    public function loadControllers(\Admin\Controller\ControllerRepository $repo)
    {
        $repo->register("triggers", new Controller\TriggersController());
    }
}

?>