<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Service;

class UserBotSignatureService extends \Traffic\Service\AbstractService
{
    const ADDITIONAL_LIST = "bots.additional.signature.dat";
    public function exists($ua)
    {
        $file = $this->_getFullPath(ADDITIONAL_LIST);
        if (file_exists($file)) {
            $list = file_get_contents($file);
            $case = new \Component\BotDetection\CheckInList("#version:2\n" . $list);
            if ($case->call($ua)) {
                return true;
            }
        }
        return false;
    }
    public function getAdditionalListCount()
    {
        $list = $this->getAdditionalList();
        return empty($list) ? 0 : mb_substr_count($list, "\n") + 1;
    }
    public function getAdditionalList()
    {
        $file = $this->_getFullPath(ADDITIONAL_LIST);
        if (!file_exists($file)) {
            return "";
        }
        return file_get_contents($file);
    }
    public function saveAdditionalList($content)
    {
        $content = str_replace(",", "\n", $content);
        $items = explode("\n", $content);
        $items = array_map(function ($item) {
            return trim($item);
        }, $items);
        array_unique($items);
        $items = array_filter($items);
        file_put_contents($this->_getFullPath(ADDITIONAL_LIST), join("\n", $items));
    }
    private function _getFullPath($list)
    {
        return ROOT . "/var/bots/" . $list;
    }
}

?>