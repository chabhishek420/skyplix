<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Device\Repository;

class LanguagesRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_data = NULL;
    const LANGUAGES_CONVERT = ["IN" => "HI"];
    public function getData()
    {
        if (!isset($this->_data)) {
            $this->_data = (include ROOT . "/application/Component/Device/dictionaries/languages.php");
        }
        return $this->_data;
    }
    public function getName($lang)
    {
        return $this->findInfo($lang);
    }
    public function findInfo($key)
    {
        $data = $this->getData();
        if (!$key) {
            return NULL;
        }
        $key = strtoupper($key);
        if (isset($data[$key])) {
            return $data[$key][\Core\Locale\LocaleService::instance()->getLanguage()];
        }
    }
    public function getLanguages()
    {
        $data = $this->getData();
        $arr = [];
        foreach ($data as $language => $names) {
            $arr[] = ["key" => $language, "name" => $names[\Core\Locale\LocaleService::instance()->getLanguage()]];
        }
        return $arr;
    }
    public function only($onlyItems)
    {
        $result = [];
        foreach ($this->getLanguages() as $lang) {
            if (in_array($lang["value"], $onlyItems)) {
                $result[] = $lang;
            }
        }
        return $result;
    }
    public function asNames($values)
    {
        if (!is_array($values)) {
            return [];
        }
        $result = [];
        foreach ($values as $key) {
            $name = $this->findInfo($key);
            if (isset($name)) {
                $result[] = $name;
            }
        }
        return $result;
    }
    public function isValid($lang)
    {
        $result = $this->_convertLanguage($lang);
        return array_key_exists($result, $this->getData());
    }
    private function _convertLanguage($lang)
    {
        if (isset(LANGUAGES_CONVERT[$lang])) {
            return LANGUAGES_CONVERT[$lang];
        }
        return $lang;
    }
}

?>