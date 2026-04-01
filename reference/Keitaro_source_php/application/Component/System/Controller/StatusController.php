<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\Controller;

class StatusController extends \Admin\Controller\BaseController
{
    public function getInfoAction()
    {
        return \Component\System\Service\StatusService::instance()->info();
    }
    public function getInstallAction()
    {
        return ["installation_method" => \Component\System\Service\StatusService::instance()->getInstallationMethod()];
    }
    public function warmupCacheAction()
    {
        \Traffic\Repository\CachedSettingsRepository::instance()->warmup();
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
        return ["size" => \Component\System\Service\StatusService::instance()->getCacheSize()];
    }
    public function restartRoadrunnerAction()
    {
        return \Traffic\RoadRunner\Server::restart();
    }
}

?>