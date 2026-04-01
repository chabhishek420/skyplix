<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Locale;

class LocaleService extends \Traffic\Service\AbstractService
{
    private $_language = NULL;
    private $_translations = NULL;
    private $_allowedLanguages = NULL;
    const DEFAULT_LANGUAGE = "en";
    const RU = "ru";
    const EN = "en";
    public function __construct()
    {
        $this->setLanguage(DEFAULT_LANGUAGE);
    }
    public function setLanguage($language = NULL)
    {
        if (empty($language)) {
            $language = DEFAULT_LANGUAGE;
        }
        if ($this->_language != $language) {
            $this->_translations = NULL;
        }
        $this->_language = $language;
    }
    public function init()
    {
        if (!empty($this->_translations)) {
            return NULL;
        }
        $this->setTranslations(\Core\ComponentManager\ComponentManager::instance()->loadTranslations($this->getLanguage()));
    }
    public static function t($key, $params = NULL)
    {
        return LocaleService::instance()->getTranslation($key, $params);
    }
    public function getTranslations($language = NULL, $parent = NULL)
    {
        if (empty($language)) {
            $language = $this->getLanguage();
        }
        $this->setLanguage($language);
        $this->init();
        if ($parent && isset($this->_translations[$parent])) {
            return $this->_translations[$parent];
        }
        return $this->_translations;
    }
    public function setTranslations($translations)
    {
        $this->_translations = $translations;
    }
    public function getLanguage($getLanguage)
    {
        return $this->_language;
    }
    public function getLanguages()
    {
        $items = [];
        foreach ($this->_allowedLanguages as $lang) {
            $items[] = ["value" => $lang, "name" => LocaleService::t("languages." . $lang)];
        }
        return $items;
    }
    public function getTranslation($key, $params = NULL)
    {
        $path = explode(".", $key);
        $translation = self::_find($path, $this->getTranslations());
        if (!isset($translation)) {
            $translation = $key;
        }
        if ($params) {
            if (!is_array($params)) {
                $params = [$params];
            }
            $args = array_merge([$translation], $params);
            $translation = call_user_func_array("sprintf", $args);
        }
        return $translation;
    }
    public function exists($key, $language = NULL)
    {
        if (empty($language)) {
            $language = $this->getLanguage();
        }
        $path = explode(".", $key);
        return (int) self::_find($path, $this->getTranslations($language));
    }
    protected function _find($path, $data)
    {
        $key = array_shift($path);
        if (!isset($data[$key])) {
            return NULL;
        }
        if (count($path)) {
            return self::_find($path, $data[$key]);
        }
        return $data[$key];
    }
}

?>