<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Controller;

class AppsFlyerController extends \Admin\Controller\BaseController
{
    public function getDescriptionAction()
    {
        $language = \Core\Locale\LocaleService::instance()->getLanguage();
        if ($language == "ru") {
            return \Component\ThirdPartyIntegration\AppsFlyer\AppsFlyerDescription::getDescriptionRu();
        }
        return \Component\ThirdPartyIntegration\AppsFlyer\AppsFlyerDescription::getDescriptionEn();
    }
}

?>