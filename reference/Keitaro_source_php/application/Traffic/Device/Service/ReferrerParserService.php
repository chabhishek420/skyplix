<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Device\Service;

class ReferrerParserService extends \Traffic\Service\AbstractService
{
    public function parse($referrer)
    {
        if (empty($referrer)) {
            return NULL;
        }
        foreach ($this->_getData() as $key => $info) {
            if (@stripos($referrer, $info["host"])) {
                return $this->_parse($info, $referrer);
            }
        }
    }
    private function _parse($info, $referrer)
    {
        $pattern = "/[&\\?]" . $info["var"] . "=([^&]*)/i";
        $keyword = NULL;
        if (preg_match($pattern, $referrer, $result)) {
            $keyword = $result[1];
            if (@stripos($referrer, $keyword) === 0) {
                return NULL;
            }
        }
        if ("UTF-8" != $info["charset"]) {
            $keyword = iconv($info["charset"], "UTF-8", $keyword);
        }
        return $keyword;
    }
    private function _getData()
    {
        return require ROOT . "/application/Traffic/Device/dictionaries/se.php";
    }
}

?>