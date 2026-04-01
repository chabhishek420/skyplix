<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Controller;

class ExportedReportsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $files = \Component\Reports\Repository\ExportedReportsRepository::instance()->all();
        return $files;
    }
    public function deleteAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        $filename = $this->getPostParam("filename");
        \Component\Clicks\Service\ExportedReportsService::instance()->delete($filename);
    }
    public function deleteAllAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        \Component\Clicks\Service\ExportedReportsService::instance()->deleteAll();
    }
}

?>