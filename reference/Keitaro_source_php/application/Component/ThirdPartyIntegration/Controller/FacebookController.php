<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Controller;

class FacebookController extends \Admin\Controller\BaseController
{
    public function getDescriptionAction()
    {
        $language = \Core\Locale\LocaleService::instance()->getLanguage();
        if ($language == "ru") {
            return \Component\ThirdPartyIntegration\Facebook\FacebookDescription::getDescriptionRu();
        }
        return \Component\ThirdPartyIntegration\Facebook\FacebookDescription::getDescriptionEn();
    }
    public function startAction()
    {
        $integrationId = $this->getParam("integration_id");
        if ($integrationId) {
            $fb = new \Component\ThirdPartyIntegration\Facebook\Facebook();
            $fb->syncCost($integrationId, true);
            $data = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->find($integrationId);
            return ["data" => $this->serialize($data, new \Component\ThirdPartyIntegration\Serializer\ThirdPartyIntegrationSerializer())];
        }
        return ["status" => false];
    }
}

?>