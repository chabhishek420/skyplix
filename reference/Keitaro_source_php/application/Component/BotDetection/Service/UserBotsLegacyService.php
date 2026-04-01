<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Service;

class UserBotsLegacyService extends \Traffic\Service\AbstractService implements \Component\BotDetection\UserBotsServiceInterface
{
    public function saveList($content)
    {
        $content = str_replace(",", "\n", $content);
        $items = explode("\n", $content);
        $items = array_map(function ($item) {
            return trim($item);
        }, $items);
        array_unique($items);
        $items = array_filter($items);
        file_put_contents(\Component\BotDetection\Repository\UserBotsLegacyRepository::instance()->getFullPath(), join("\n", $items));
    }
    protected function _throwUnimplemented()
    {
        throw new \Exception(\Core\Locale\LocaleService::t("settings.bots.need_migration"));
    }
    public function addToList($content)
    {
        $this->_throwUnimplemented();
    }
    public function excludeFromList($content)
    {
        $this->_throwUnimplemented();
    }
    public function cleanList()
    {
        $this->_throwUnimplemented();
    }
}

?>