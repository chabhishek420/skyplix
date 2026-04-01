<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Controller;

class LegacyMigrationsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        return $this->serialize(\Component\Migrations\Repository\LegacyMigrationsRepository::instance()->getMigrations(), new \Component\Migrations\Serializer\LegacyMigrationSerializer());
    }
    public function schemaInfoAction()
    {
        return ["current_version" => \Component\Migrations\Service\LegacyMigrationRunnerService::instance()->getCurrentVersion(), "last_migration_version" => \Component\Migrations\Service\LegacyMigrationRunnerService::instance()->getLastVersion()];
    }
    public function runAction()
    {
        while (!$this->isAdmin()) {
            $this->throwDeny();
        }
        if (!$this->isPost()) {
            return NULL;
        }
        $version = (int) $this->getParam("version");
        $runner = new \Component\Migrations\Service\LegacyMigrationRunnerService();
        if ($version) {
            try {
                $runner->run($version);
            } catch (\ADODB_Exception $e) {
                if (!strstr($e->getMessage(), "Duplicate column")) {
                    $this->throwError($e->getMessage());
                }
            }
        } else {
            $runner->runAll();
        }
    }
}

?>