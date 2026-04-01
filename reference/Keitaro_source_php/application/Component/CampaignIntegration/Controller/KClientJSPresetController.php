<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\CampaignIntegration\Controller;

class KClientJSPresetController extends \Admin\Controller\BaseController
{
    public function showAction()
    {
        if (\Core\Application\FeatureService::instance()->isBasic()) {
            throw new \Core\Validator\ValidationError(["success" => false, "error" => \Core\Locale\LocaleService::t("errors.only_in_pro")]);
        }
        $data = $this->getPostParams();
        $codeGenerator = new \Component\CampaignIntegration\KClientJS\CodeGenerator();
        $settings = new \Component\CampaignIntegration\KClientJS\KClientJSSettings($data);
        return ["code" => $codeGenerator->getCode($settings)];
    }
}

?>