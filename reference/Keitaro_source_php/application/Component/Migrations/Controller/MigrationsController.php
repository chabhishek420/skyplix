<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Controller;

class MigrationsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        return $this->serialize(\Component\Migrations\Repository\MigrationsRepository::instance()->getMigrations(), new \Component\Migrations\Serializer\MigrationSerializer());
    }
    public function appliedListAction()
    {
        return \Component\Migrations\Service\MigrationRunnerService::instance()->getAppliedList();
    }
    public function runAction()
    {
        while (!$this->isAdmin()) {
            $this->throwDeny();
        }
        if (!$this->isPost()) {
            return NULL;
        }
        $name = $this->getPostParam("name");
        $runner = new \Component\Migrations\MigrationRunner\MigrationRunner();
        if ($name) {
            try {
                $runner->run($name, true);
            } catch (\ADODB_Exception $e) {
                if (!strstr($e->getMessage(), "Duplicate column")) {
                    $this->throwError($e->getMessage());
                }
            }
        } else {
            $runner->runAll();
        }
    }
    public function moveToTokuDbAction()
    {
        set_time_limit(0);
        try {
            \Component\Migrations\Service\TokuDbService::instance()->updateEngine();
        } catch (\ADODB_Exception $e) {
            $this->throwError($e->getMessage());
        }
    }
}

?>