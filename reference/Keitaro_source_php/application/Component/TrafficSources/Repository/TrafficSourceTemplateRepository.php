<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\TrafficSources\Repository;

class TrafficSourceTemplateRepository extends \Traffic\Repository\AbstractBaseRepository
{
    const PATH = NULL;
    public function getData()
    {
        if (!is_readable(PATH)) {
            return [];
        }
        return include PATH;
    }
    protected function _convert($key, $info)
    {
        $instruction = NULL;
        $language = \Core\Locale\LocaleService::instance()->getLanguage();
        if (isset($info["instruction"]) && isset($info["instruction"][\Core\Locale\LocaleService::instance()->getLanguage()])) {
            $instruction = $info["instruction"][$language];
        }
        if (is_array($info["name"])) {
            $name = $info["name"][$language];
        } else {
            $name = $info["name"];
        }
        return ["value" => $key, "name" => ucfirst($name), "group" => $info["group"], "instruction" => $instruction, "parameters" => $info["parameters"], "postback_url" => isset($info["postback"]) ? $info["postback"] : ""];
    }
    public function get($name)
    {
        $data = $this->getData();
        if (isset($data[$name])) {
            return $this->_convert($name, $data[$name]);
        }
        return NULL;
    }
    public function getTemplates()
    {
        $data = $this->getData();
        $items = [];
        ksort($data);
        foreach ($data as $key => $info) {
            $items[] = $this->_convert($key, $info);
        }
        return $items;
    }
}

?>