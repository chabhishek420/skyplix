<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Controller;

class ThirdPartyIntegrationController extends \Admin\Controller\BaseController
{
    public function createAction()
    {
        $this->_checkPro();
        $allParams = $this->getPostParams();
        $integration = $this->getParam("integration");
        if (!$integration) {
            throw new \Core\Application\Exception\Error("Param integration is required");
        }
        $allParams["integration"] = $integration;
        $result = \Component\ThirdPartyIntegration\Service\ThirdPartyIntegrationService::instance()->create(["integration" => $integration, "settings" => $allParams]);
        return $this->serialize($result, new \Component\ThirdPartyIntegration\Serializer\ThirdPartyIntegrationSerializer());
    }
    public function updateAction()
    {
        $this->_checkPro();
        $allParams = $this->getPostParams();
        $id = $this->getParam("id");
        if ($id) {
            \Component\ThirdPartyIntegration\Service\ThirdPartyIntegrationService::instance()->updateValues($id, $allParams);
            $data = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->find($id);
            return ["data" => $this->serialize($data, new \Component\ThirdPartyIntegration\Serializer\ThirdPartyIntegrationSerializer())];
        }
        return $this->throwNotFound("No ID provided");
    }
    public function getAction()
    {
        $this->_checkPro();
        $integration = $this->getParam("integration");
        if (isset($integration)) {
            $data = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->findByIntegrationName($integration);
            return ["data" => $this->serialize($data, new \Component\ThirdPartyIntegration\Serializer\ThirdPartyIntegrationSerializer())];
        }
        return [];
    }
    public function findAction()
    {
        $this->_checkPro();
        $id = $this->getParam("id");
        if (isset($id)) {
            $data = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->find($id);
            return ["data" => $this->serialize($data, new \Component\ThirdPartyIntegration\Serializer\ThirdPartyIntegrationSerializer())];
        }
        return $this->throwNotFound("No ID provided");
    }
    public function getByCampaignIdAction()
    {
        $this->_checkPro();
        $id = $this->getParam("id");
        $key = \Component\ThirdPartyIntegration\Repository\TPICampaignAssociationRepository::instance()->findIdByCampaignId($id);
        return ["default" => $key];
    }
    public function deleteAction()
    {
        $this->_checkPro();
        $id = $this->getParam("id");
        if (isset($id)) {
            \Component\ThirdPartyIntegration\Service\ThirdPartyIntegrationService::instance()->deleteById($id);
            return ["success" => true];
        }
        return $this->throwNotFound("No ID provided");
    }
    public function getSettingsIntegrationAction()
    {
        $param = $this->getParam("param");
        return [$param => \Traffic\Repository\CachedSettingsRepository::instance()->get($param)];
    }
    public function updateSettingsIntegrationAction()
    {
        $param = $this->getParam("param");
        $key = $this->getParam($param);
        $newSettings = [$param => $key];
        if (!is_null($key)) {
            \Traffic\Service\SettingsService::instance()->updateValues($newSettings);
        }
        return \Traffic\Settings\Repository\SettingsRepository::instance()->allAsHash(array_keys($newSettings));
    }
    private function _checkPro()
    {
        if (\Core\Application\FeatureService::instance()->isBasic()) {
            throw new \Core\Validator\ValidationError(["type" => [\Core\Locale\LocaleService::t("third_party_integration.pro_error")]]);
        }
    }
}

?>