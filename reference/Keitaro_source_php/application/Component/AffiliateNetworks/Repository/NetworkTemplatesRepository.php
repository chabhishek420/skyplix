<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AffiliateNetworks\Repository;

class NetworkTemplatesRepository extends \Traffic\Repository\AbstractBaseRepository
{
    const PATH = NULL;
    public function getData()
    {
        if (!is_readable(PATH)) {
            return [];
        }
        return include PATH;
    }
    protected function convertPreset($key, $info)
    {
        $instruction = NULL;
        if (isset($info["instruction"]) && isset($info["instruction"][\Core\Locale\LocaleService::instance()->getLanguage()])) {
            $instruction = $info["instruction"][\Core\Locale\LocaleService::instance()->getLanguage()];
        }
        if ($info["name"] == "other") {
            $isOther = true;
            $name = \Core\Locale\LocaleService::t("postbacks.other");
        } else {
            $isOther = false;
            $name = $info["name"];
        }
        if (empty($info["offer_param"])) {
            throw new \Core\Application\Exception\Error("Aff. Network \"" . $name . "\" doesn't have \"offer_param\"");
        }
        $info["params"]["from"] = $name;
        return ["other" => $isOther, "value" => $key, "name" => ucfirst($name), "params" => $info["params"], "offer_param" => isset($info["offer_param"]) ? $info["offer_param"] : NULL, "instruction" => $instruction, "format" => isset($info["format"]) ? $info["format"] : NULL];
    }
    public function getTemplate($name)
    {
        $data = $this->getData();
        if (isset($data[$name])) {
            return $this->convertPreset($name, $data[$name]);
        }
        return NULL;
    }
    public function getTemplates($includeOther = false)
    {
        $data = $this->getData();
        $items = [];
        $items[] = ["value" => "", "name" => \Core\Locale\LocaleService::t("affiliate_networks.no_template"), "instruction" => NULL, "params" => "", "offer_param" => ""];
        ksort($data);
        foreach ($data as $key => $info) {
            if ($info["name"] != "other" || $includeOther) {
                $items[] = $this->convertPreset($key, $info);
            }
        }
        return $items;
    }
    public function getSecret()
    {
        $key = trim(\Traffic\Service\ConfigService::instance()->get("system", "postback_key"));
        if (empty($key)) {
            $key = substr(md5(SALT), 15, 7);
        }
        return $key;
    }
}

?>