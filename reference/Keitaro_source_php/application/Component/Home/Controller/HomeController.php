<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Home\Controller;

class HomeController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        $this->header(\Traffic\Response\ContentType::HEADER, \Traffic\Response\ContentType::HTML);
        return $this->renderView(ROOT . "/application/layouts/index.phtml", ["title" => "Welcome", "translations" => \Core\Locale\LocaleService::instance()->getTranslations(), "favicon" => \Component\Branding\Repository\BrandingRepository::instance()->getFavicon(), "kData" => \Component\Home\Service\JsConfigService::instance()->get($this->getServerRequest())]);
    }
}

?>