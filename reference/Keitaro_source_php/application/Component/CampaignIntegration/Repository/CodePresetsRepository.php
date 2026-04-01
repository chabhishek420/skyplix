<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\CampaignIntegration\Repository;

class CodePresetsRepository extends \Traffic\Repository\AbstractBaseRepository
{
    public function __construct()
    {
        $this->_data = (include ROOT . "/application/Component/CampaignIntegration/data/code_presets.php");
    }
    public function getPresets()
    {
        $result = [];
        foreach ($this->_data as $preset) {
            $result[] = $this->_prepare($preset, \Core\Locale\LocaleService::instance()->getLanguage());
        }
        return $result;
    }
    public function get($id)
    {
        foreach ($this->_data as $item) {
            if (isset($item["id"]) && $id == $item["id"]) {
                return $this->_prepare($item, \Core\Locale\LocaleService::instance()->getLanguage());
            }
        }
    }
    private function _prepare($preset, $locale)
    {
        if (empty($preset)) {
            return NULL;
        }
        $data = ["id" => isset($preset["id"]) ? $preset["id"] : "", "name" => is_array($preset["name"]) ? $preset["name"][$locale] : $preset["name"], "instruction" => isset($preset["instructions"]) ? $preset["instructions"][$locale] : NULL, "instruction_2" => isset($preset["instructions_2"]) ? $preset["instructions_2"][$locale] : NULL, "code" => empty($preset["code"]) ? "" : $preset["code"], "offer_code" => empty($preset["offer_code"]) ? "" : $preset["offer_code"], "postback_code" => empty($preset["postback_code"]) ? "" : $preset["postback_code"], "add_params" => isset($preset["add_params"]) ? $this->_prepareAddParams($preset["add_params"]) : "", "group" => $preset["group"], "group_translated" => \Core\Locale\LocaleService::t("integration.groups." . $preset["group"]), "settings" => isset($preset["settings"]) ? $preset["settings"] : NULL, "is_beta" => isset($preset["beta"]) ? $preset["beta"] : NULL, "is_pro_only" => false];
        if (isset($preset["edition"]) && $preset["edition"] === "pro" && in_array(\Core\Application\FeatureService::instance()->getEdition(), ["trial", "pro"])) {
            $data["is_pro_only"] = true;
        }
        return $data;
    }
    private function _prepareAddParams($addParams)
    {
        $params = explode("&", $addParams);
        $params = array_map(function ($paramString) {
            if (strpos($paramString, "frm") === 0) {
                list($paramName, $paramValue) = explode("=", $paramString);
                return $paramName . uniqid() . "=" . $paramValue . uniqid();
            }
            return $paramString;
        }, $params);
        return implode("&", $params);
    }
}

?>