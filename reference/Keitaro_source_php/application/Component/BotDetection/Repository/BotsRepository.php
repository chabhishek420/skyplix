<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Repository;

class BotsRepository extends \Traffic\Service\AbstractService
{
    protected $_files = ["bots.dat", "my.ip.dat"];
    private $_gotLists = [];
    public function exists($ip)
    {
        return \Component\GeoDb\Keitaro\KeitaroBotDb2Adapter::instance()->isBot($ip);
    }
    public function existsLegacy($ip)
    {
        foreach ($this->_files as $file) {
            if (empty($this->_gotLists[$file])) {
                $this->_gotLists[$file] = $this->parseOldList($file);
            }
            if (!empty($this->_gotLists[$file])) {
                $case = new \Component\BotDetection\CheckInList($this->_gotLists[$file]);
                if ($case->call($ip)) {
                    return true;
                }
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
    public function ready()
    {
        return \Component\GeoDb\Keitaro\KeitaroBotDb2Adapter::instance()->databaseExists();
    }
}

?>