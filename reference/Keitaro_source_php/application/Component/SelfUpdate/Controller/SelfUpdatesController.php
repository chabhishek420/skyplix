<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\SelfUpdate\Controller;

class SelfUpdatesController extends \Admin\Controller\BaseController
{
    public function getUpdateAction()
    {
        $update = \Component\SelfUpdate\Service\SystemUpdaterService::instance()->getUpdate(phpversion(), TDS_VERSION);
        if ($update) {
            return ["version" => $update["version"], "changelog" => \Component\SelfUpdate\Service\SystemUpdaterService::instance()->getChangelog()];
        }
        return ["new_migrations" => !\Component\Migrations\Service\MigrationRunnerService::instance()->isAllApplied()];
    }
    public function checkAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        $selfChecker = new \Component\SelfUpdate\SelfChecker\SelfChecker();
        $output = $selfChecker->checkAll();
        if ($selfChecker->criticalErrorExists()) {
            $criticalError = true;
        } else {
            $criticalError = false;
        }
        return $this->renderView(ROOT . "/application/Component/SelfUpdate/views/check.phtml", ["criticalError" => $criticalError, "content" => nl2br($output), "title" => $this->t("self_check.title"), "recheck" => $this->t("self_check.recheck"), "continue" => $this->t("self_check.continue"), "redirect" => $this->t("self_check.redirect"), "critical_errors" => $this->t("self_check.critical_errors")], ROOT . "/application/layouts/system.phtml");
    }
    public function updateAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        if ($this->isPost()) {
            $case = new \Component\SelfUpdate\KeitaroUpdater\KeitaroUpdater();
            $case->update();
        }
    }
    public function isOldPHPAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        return ["isOldPHP" => \Component\SelfUpdate\Service\SystemUpdaterService::instance()->isPHPOutdated()];
    }
}

?>