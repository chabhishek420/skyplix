<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\CampaignIntegration\Controller;

class CodePresetsController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        return \Component\CampaignIntegration\Repository\CodePresetsRepository::instance()->getPresets();
    }
    public function showAction()
    {
        return \Component\CampaignIntegration\Repository\CodePresetsRepository::instance()->get($this->getParam("id"));
    }
    public function downloadClientAction()
    {
        $this->header("Content-Description", "File Transfer");
        $this->header("Content-type", "application/octet-stream");
        $this->header("Content-Disposition", "attachment; filename=kclient.php");
        $this->header("Expires", "0");
        $this->header("Cache-Control", "must-revalidate");
        $this->header("Pragma", "public");
        return file_get_contents(ROOT . "/application/kclients/kclient.php");
    }
    public function downloadClientV2Action()
    {
        $this->header("Content-Description", "File Transfer");
        $this->header("Content-type", "application/octet-stream");
        $this->header("Content-Disposition", "attachment; filename=kclient.php");
        $this->header("Expires", "0");
        $this->header("Cache-Control", "must-revalidate");
        $this->header("Pragma", "public");
        return file_get_contents(ROOT . "/application/kclients/kclient_v2.php");
    }
}

?>