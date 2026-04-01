<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Repository;

class UserBotsLegacyRepository extends \Traffic\Service\AbstractService implements UserBotsRepositoryInterface
{
    private $_gotList = NULL;
    const LEGACY_USER_LIST = "bots.additional.dat";
    public function exists($ip)
    {
        if (empty($this->_gotList)) {
            $this->_gotList = $this->parseOldList(LEGACY_USER_LIST);
        }
        if (!empty($this->_gotList)) {
            $case = new \Component\BotDetection\CheckInList($this->_gotList);
            if ($case->call($ip)) {
                return true;
            }
        }
        return false;
    }
    public function parseOldList($list)
    {
        $file = ROOT . "/var/bots/" . $list;
        if (file_exists($file)) {
            return file_get_contents($file);
        }
        return NULL;
    }
    public function getListCount()
    {
        $list = $this->getList();
        return empty($list) ? 0 : mb_substr_count($list, "\n") + 1;
    }
    public function getList()
    {
        $file = $this->getFullPath();
        if (!file_exists($file)) {
            return "";
        }
        return file_get_contents($file);
    }
    public function clearList()
    {
        $path = $this->getFullPath();
        if (is_file($path)) {
            @unlink($path);
        }
    }
    public function getFullPath()
    {
        return ROOT . "/var/bots/" . LEGACY_USER_LIST;
    }
}

?>